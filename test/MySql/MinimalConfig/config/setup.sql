drop table if exists TST_FOO1;

create table TST_FOO1( tst_c00 int           not null
,                      tst_c01 smallint      not null
,                      tst_c02 tinyint
,                      tst_c03 mediumint
,                      tst_c04 bigint
,                      tst_c05 decimal(10,2)
,                      tst_c06 float
,                      tst_c07 double
,                      tst_c08 bit(8)
,                      tst_c09 date
,                      tst_c10 datetime
,                      tst_c11 timestamp
,                      tst_c12 time
,                      tst_c13 year
,                      tst_c14 char(10)
,                      tst_c15 varchar(10)
,                      tst_c16 binary(10)
,                      tst_c17 varbinary(10)
,                      tst_c18 tinyblob
,                      tst_c19 blob
,                      tst_c20 mediumblob
,                      tst_c21 longblob
,                      tst_c22 tinytext
,                      tst_c23 text
,                      tst_c24 mediumtext
,                      tst_c25 longtext
,                      tst_c26 enum('a','b')
,                      tst_c27 set('a','b'))
engine=myisam
;
