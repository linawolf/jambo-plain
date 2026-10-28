#
# Reports of a waste collection that did not happen. Written by the form
# framework, read once a day by jambo:waste:digest.
#
# uid and the primary key are spelled out: the schema analyzer added pid,
# tstamp, crdate and deleted from the TCA, but no uid, and the digest needs a
# key to mark what it has already sent.
#
CREATE TABLE tx_jambotheme_wastereport (
    uid int(11) UNSIGNED NOT NULL auto_increment,
    pid int(11) UNSIGNED DEFAULT 0 NOT NULL,

    ward varchar(128) DEFAULT '' NOT NULL,
    street varchar(255) DEFAULT '' NOT NULL,
    missed_on varchar(10) DEFAULT '' NOT NULL,
    waste_type varchar(64) DEFAULT '' NOT NULL,
    details text,
    reporter_name varchar(255) DEFAULT '' NOT NULL,
    reporter_email varchar(255) DEFAULT '' NOT NULL,
    reporter_phone varchar(64) DEFAULT '' NOT NULL,
    digest_sent_at int(11) UNSIGNED DEFAULT 0 NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid),
    KEY pending (digest_sent_at),
    KEY retention (crdate)
);

#
# A publication date for a notice page. The pages table has no field for it:
# "last updated" means something else and feeds the sitemap, and the date of a
# notice has to be the one the council published it on, whatever was edited
# afterwards.
#
CREATE TABLE pages (
    tx_jambotheme_notice_date int(11) DEFAULT 0 NOT NULL,

    KEY jambo_notices (pid,tx_jambotheme_notice_date)
);
