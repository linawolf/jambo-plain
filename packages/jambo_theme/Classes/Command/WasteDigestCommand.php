<?php

declare(strict_types=1);

namespace Jambo\Theme\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mime\Part\DataPart;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Mail\MailMessage;

/**
 * Sends the reports of missed waste collections that have not been sent yet as
 * one CSV, and marks them so the next run does not repeat them. Afterwards it
 * deletes the reports the county is no longer allowed to keep.
 *
 * The retention rule rides along with the digest on purpose: one cron line that
 * cannot be half-configured, so there is no way to end up mailing the reports
 * daily while quietly keeping them forever.
 *
 * Meant to run once a day:
 *   0 6 * * * /var/www/html/vendor/bin/typo3 jambo:waste:digest
 * or as a scheduler task, since the command is schedulable.
 */
#[AsCommand(
    name: 'jambo:waste:digest',
    description: 'Mails the collected reports of missed waste collections as a CSV and deletes the expired ones.',
)]
final class WasteDigestCommand extends Command
{
    private const TABLE = 'tx_jambotheme_wastereport';

    /**
     * How long a report stays in the database. The reports carry a name, an
     * email address, a telephone number and an address, so they are deleted
     * for good rather than flagged as deleted - a flagged row still holds the
     * data.
     */
    private const RETENTION_DAYS = 90;

    private const COLUMNS = [
        'uid' => 'Report',
        'crdate' => 'Reported at',
        'ward' => 'Ward',
        'street' => 'Street or plot',
        'missed_on' => 'Collection missed on',
        'waste_type' => 'Kind of waste',
        'details' => 'Details',
        'reporter_name' => 'Name',
        'reporter_email' => 'Email',
        'reporter_phone' => 'Telephone',
    ];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly MailerInterface $mailer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('recipient', 'r', InputOption::VALUE_REQUIRED, 'Where the list goes', 'waste@jambo-county.example')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print the CSV instead of sending it, and mark nothing')
            ->addOption('even-if-empty', null, InputOption::VALUE_NONE, 'Send a mail although there is nothing to report')
            ->addOption(
                'retention-days',
                null,
                InputOption::VALUE_REQUIRED,
                'Delete reports older than this many days, 0 keeps them forever',
                (string)self::RETENTION_DAYS
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $retentionDays = (int)$input->getOption('retention-days');
        if ($retentionDays < 0) {
            $io->error('--retention-days cannot be negative.');
            return Command::INVALID;
        }

        $reports = $this->fetchUnsentReports();

        $dryRun = (bool)$input->getOption('dry-run');

        if ($reports === [] && !$input->getOption('even-if-empty')) {
            $io->success('No new reports, nothing sent.');
            $this->reportRetention($io, $retentionDays, $dryRun);
            return Command::SUCCESS;
        }

        $csv = $this->buildCsv($reports);

        if ($dryRun) {
            $io->section(sprintf('%d report(s), nothing sent and nothing marked', count($reports)));
            $output->writeln($csv);
            $this->reportRetention($io, $retentionDays, true);
            return Command::SUCCESS;
        }

        $today = date('Y-m-d');
        $mail = new MailMessage();
        $mail
            ->to($input->getOption('recipient'))
            ->subject(sprintf('Missed waste collections, %s (%d report(s))', $today, count($reports)))
            ->text($this->buildBody($reports, $today))
            ->addPart(new DataPart($csv, 'missed-collections-' . $today . '.csv', 'text/csv'));
        // MailMessage::send() is gone in v14; the mailer is a service now.
        $this->mailer->send($mail);

        $this->markAsSent(array_column($reports, 'uid'));

        $io->success(sprintf('Sent %d report(s) to %s.', count($reports), $input->getOption('recipient')));

        // Only now, with the mail gone out, is it safe to drop old records.
        $this->reportRetention($io, $retentionDays, false);

        return Command::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchUnsentReports(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        // The records are written by the form framework, never edited, so the
        // deleted flag is the only restriction worth applying.
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder
            ->select(...array_keys(self::COLUMNS))
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('digest_sent_at', 0),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->orderBy('crdate', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @param array<int, array<string, mixed>> $reports
     */
    private function buildCsv(array $reports): string
    {
        $handle = fopen('php://temp', 'r+');
        // A BOM, so the column with the Swahili characters opens correctly in
        // the spreadsheet programs the office is likely to use.
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, array_values(self::COLUMNS), ',', '"', '');
        foreach ($reports as $report) {
            $row = [];
            foreach (array_keys(self::COLUMNS) as $column) {
                $value = (string)($report[$column] ?? '');
                if ($column === 'crdate') {
                    $value = date('Y-m-d H:i', (int)$report[$column]);
                }
                $row[] = $value;
            }
            fputcsv($handle, $row, ',', '"', '');
        }
        rewind($handle);
        $csv = (string)stream_get_contents($handle);
        fclose($handle);
        return $csv;
    }

    /**
     * @param array<int, array<string, mixed>> $reports
     */
    private function buildBody(array $reports, string $today): string
    {
        if ($reports === []) {
            return sprintf("No missed collections were reported up to %s.\n", $today);
        }
        $wards = array_count_values(array_map(static fn(array $r): string => (string)$r['ward'], $reports));
        arsort($wards);
        $lines = [
            sprintf('%d missed collection(s) reported up to %s.', count($reports), $today),
            '',
            'By ward:',
        ];
        foreach ($wards as $ward => $count) {
            $lines[] = sprintf('  %-16s %d', $ward !== '' ? $ward : 'not given', $count);
        }
        $lines[] = '';
        $lines[] = 'The full list is attached as a CSV file.';
        return implode("\n", $lines) . "\n";
    }

    /**
     * Applies the retention rule and says what it did, or - in a dry run - what
     * it would do.
     */
    private function reportRetention(SymfonyStyle $io, int $retentionDays, bool $dryRun): void
    {
        if ($retentionDays === 0) {
            $io->writeln('Retention is switched off, no report was deleted.');
            return;
        }

        $cutoff = $this->retentionCutoff($retentionDays);
        $expired = $this->countExpiredReports($cutoff);

        // Kept past their date because nobody has received them - worth saying
        // out loud, because it means the digest mail has not been arriving.
        $stranded = $this->countStrandedReports($cutoff);
        if ($stranded > 0) {
            $io->warning(sprintf(
                '%d report(s) are older than %d days but have never been mailed, so they were kept. '
                . 'Check that the digest mail is going out.',
                $stranded,
                $retentionDays
            ));
        }

        if ($expired === 0) {
            $io->writeln(sprintf('No report is older than %d days.', $retentionDays));
            return;
        }

        if ($dryRun) {
            $io->writeln(sprintf(
                '%d report(s) are older than %d days and would be deleted.',
                $expired,
                $retentionDays
            ));
            return;
        }

        $deleted = $this->deleteExpiredReports($cutoff);
        $io->writeln(sprintf('Deleted %d report(s) older than %d days.', $deleted, $retentionDays));
    }

    /**
     * Midnight of the day the retention period ran out, so a run at 06:00 and a
     * run at 18:00 on the same day delete the same records.
     */
    private function retentionCutoff(int $retentionDays): int
    {
        return (int)strtotime('today midnight -' . $retentionDays . ' days');
    }

    private function countExpiredReports(int $cutoff): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder
            ->count('uid')
            ->from(self::TABLE)
            ->where(...$this->expiredConstraints($queryBuilder, $cutoff))
            ->executeQuery()
            ->fetchOne();
    }

    private function countStrandedReports(int $cutoff): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder
            ->count('uid')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->lt('crdate', $queryBuilder->createNamedParameter($cutoff, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('digest_sent_at', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne();
    }

    private function deleteExpiredReports(int $cutoff): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder
            ->delete(self::TABLE)
            ->where(...$this->expiredConstraints($queryBuilder, $cutoff))
            ->executeStatement();
    }

    /**
     * Old enough, and either already mailed to the waste department or thrown
     * away in the backend. A report that nobody has received yet is kept even
     * past the retention period: if the digest mail has been failing for three
     * months, deleting the evidence is the wrong half of the job to automate.
     *
     * @return array<int, \TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression|string>
     */
    private function expiredConstraints(QueryBuilder $queryBuilder, int $cutoff): array
    {
        return [
            $queryBuilder->expr()->lt('crdate', $queryBuilder->createNamedParameter($cutoff, Connection::PARAM_INT)),
            $queryBuilder->expr()->or(
                $queryBuilder->expr()->gt('digest_sent_at', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT))
            ),
        ];
    }

    /**
     * @param array<int, int|string> $uids
     */
    private function markAsSent(array $uids): void
    {
        if ($uids === []) {
            return;
        }
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE);
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $connection->executeStatement(
            sprintf(
                'UPDATE %s SET digest_sent_at = ? WHERE uid IN (%s)',
                self::TABLE,
                implode(',', array_map('intval', $uids))
            ),
            [time()]
        );
    }
}
