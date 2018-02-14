#
# This file is part of the TYPO3 CMS project.
#
# It is free software; you can redistribute it and/or modify it under
# the terms of the GNU General Public License, either version 2
# of the License, or any later version.
#
# For the full copyright and license information, please read the
# LICENSE.txt file that was distributed with this source code.
#
# The TYPO3 project - inspiring people to share!


#
# Table structure for table 'tx_dpf_domain_model_documenttype'
#
CREATE TABLE tx_dpf_domain_model_documenttype (

  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,

  name varchar(255) DEFAULT '' NOT NULL,
  display_name varchar(255) DEFAULT '' NOT NULL,
  virtual tinyint(1) unsigned DEFAULT '0' NOT NULL,
  metadata_page int(11) unsigned DEFAULT '0' NOT NULL,

  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
  starttime int(11) unsigned DEFAULT '0' NOT NULL,
  endtime int(11) unsigned DEFAULT '0' NOT NULL,

  t3ver_oid int(11) DEFAULT '0' NOT NULL,
  t3ver_id int(11) DEFAULT '0' NOT NULL,
  t3ver_wsid int(11) DEFAULT '0' NOT NULL,
  t3ver_label varchar(255) DEFAULT '' NOT NULL,
  t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
  t3ver_stage int(11) DEFAULT '0' NOT NULL,
  t3ver_count int(11) DEFAULT '0' NOT NULL,
  t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
  t3ver_move_id int(11) DEFAULT '0' NOT NULL,

  sys_language_uid int(11) DEFAULT '0' NOT NULL,
  l10n_parent int(11) DEFAULT '0' NOT NULL,
  l10n_diffsource mediumblob,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid,t3ver_wsid),
  KEY language (l10n_parent,sys_language_uid)

);

#
# Table structure for table 'tx_dpf_domain_model_document'
#
CREATE TABLE tx_dpf_domain_model_document (

  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,

  title varchar(1024) DEFAULT '' NOT NULL,
  authors varchar(1024) DEFAULT '' NOT NULL,
  xml_data text NOT NULL,
  slub_info_data text NOT NULL,
  document_type int(11) unsigned default '0',
  object_identifier varchar(255) DEFAULT '' NOT NULL,
  reserved_object_identifier varchar(255) DEFAULT '' NOT NULL,
  process_number varchar(255) DEFAULT '' NOT NULL,
  state varchar(255) DEFAULT '' NOT NULL,
  transfer_status varchar(255) DEFAULT '' NOT NULL,
  transfer_date int(11) DEFAULT '0' NOT NULL,
  date_issued varchar(255) DEFAULT '' NOT NULL,
  changed tinyint(1) unsigned DEFAULT '0' NOT NULL,
  valid tinyint(1) unsigned DEFAULT '0' NOT NULL,

  file int(11) unsigned DEFAULT '0' NOT NULL,

  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
  starttime int(11) unsigned DEFAULT '0' NOT NULL,
  endtime int(11) unsigned DEFAULT '0' NOT NULL,

  t3ver_oid int(11) DEFAULT '0' NOT NULL,
  t3ver_id int(11) DEFAULT '0' NOT NULL,
  t3ver_wsid int(11) DEFAULT '0' NOT NULL,
  t3ver_label varchar(255) DEFAULT '' NOT NULL,
  t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
  t3ver_stage int(11) DEFAULT '0' NOT NULL,
  t3ver_count int(11) DEFAULT '0' NOT NULL,
  t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
  t3ver_move_id int(11) DEFAULT '0' NOT NULL,

  sys_language_uid int(11) DEFAULT '0' NOT NULL,
  l10n_parent int(11) DEFAULT '0' NOT NULL,
  l10n_diffsource mediumblob,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid,t3ver_wsid),
  KEY language (l10n_parent,sys_language_uid)

);

#
# Table structure for table 'tx_dpf_domain_model_metadatagroup'
#
CREATE TABLE tx_dpf_domain_model_metadatagroup (

  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,

  name varchar(255) DEFAULT '' NOT NULL,
  display_name varchar(255) DEFAULT '' NOT NULL,
  backend_only tinyint(1) unsigned DEFAULT '0' NOT NULL,
  mandatory tinyint(1) unsigned DEFAULT '0' NOT NULL,
  max_iteration int(11) DEFAULT '0' NOT NULL,
  mapping_for_reading varchar(1024) DEFAULT '' NOT NULL,
  mapping varchar(1024) DEFAULT '' NOT NULL,
  mods_extension_mapping varchar(1024) DEFAULT '' NOT NULL,
  mods_extension_reference varchar(1024) DEFAULT '' NOT NULL,
  info_text text NOT NULL,
  metadata_object int(11) unsigned DEFAULT '0' NOT NULL,

  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
  starttime int(11) unsigned DEFAULT '0' NOT NULL,
  endtime int(11) unsigned DEFAULT '0' NOT NULL,

  t3ver_oid int(11) DEFAULT '0' NOT NULL,
  t3ver_id int(11) DEFAULT '0' NOT NULL,
  t3ver_wsid int(11) DEFAULT '0' NOT NULL,
  t3ver_label varchar(255) DEFAULT '' NOT NULL,
  t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
  t3ver_stage int(11) DEFAULT '0' NOT NULL,
  t3ver_count int(11) DEFAULT '0' NOT NULL,
  t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
  t3ver_move_id int(11) DEFAULT '0' NOT NULL,

  sys_language_uid int(11) DEFAULT '0' NOT NULL,
  l10n_parent int(11) DEFAULT '0' NOT NULL,
  l10n_diffsource mediumblob,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid,t3ver_wsid),
  KEY language (l10n_parent,sys_language_uid)

);

#
# Table structure for table 'tx_dpf_domain_model_metadataobject'
#
CREATE TABLE tx_dpf_domain_model_metadataobject (

  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,

  metadatagroup int(11) unsigned DEFAULT '0' NOT NULL,

  name varchar(255) DEFAULT '' NOT NULL,
  display_name varchar(255) DEFAULT '' NOT NULL,
  max_iteration int(11) DEFAULT '0' NOT NULL,
  mandatory tinyint(1) unsigned DEFAULT '0' NOT NULL,
  data_type varchar(255) DEFAULT '' NOT NULL,
  validation varchar(255) DEFAULT '' NOT NULL,
  mapping varchar(255) DEFAULT '' NOT NULL,
  mods_extension tinyint(1) unsigned DEFAULT '0' NOT NULL,
  input_field int(11) DEFAULT '0' NOT NULL,
  input_option_list int(11) unsigned default '0',
  default_value text NOT NULL,
  fill_out_service varchar(255) DEFAULT '' NOT NULL,
  backend_only tinyint(1) unsigned DEFAULT '0' NOT NULL,
  consent tinyint(1) unsigned DEFAULT '0' NOT NULL,
  linked_field_uid varchar(255) DEFAULT '' NOT NULL,

  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
  starttime int(11) unsigned DEFAULT '0' NOT NULL,
  endtime int(11) unsigned DEFAULT '0' NOT NULL,

  t3ver_oid int(11) DEFAULT '0' NOT NULL,
  t3ver_id int(11) DEFAULT '0' NOT NULL,
  t3ver_wsid int(11) DEFAULT '0' NOT NULL,
  t3ver_label varchar(255) DEFAULT '' NOT NULL,
  t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
  t3ver_stage int(11) DEFAULT '0' NOT NULL,
  t3ver_count int(11) DEFAULT '0' NOT NULL,
  t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
  t3ver_move_id int(11) DEFAULT '0' NOT NULL,

  sys_language_uid int(11) DEFAULT '0' NOT NULL,
  l10n_parent int(11) DEFAULT '0' NOT NULL,
  l10n_diffsource mediumblob,

  sorting int(11) unsigned DEFAULT '0' NOT NULL,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid,t3ver_wsid),
  KEY language (l10n_parent,sys_language_uid)

);

#
# Table structure for table 'tx_dpf_domain_model_documenttransferlog'
#
CREATE TABLE tx_dpf_domain_model_documenttransferlog (

  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,

  date int(11) DEFAULT '0' NOT NULL,
  response text NOT NULL,
  curl_error text NOT NULL,
  action varchar(255) DEFAULT '' NOT NULL,
  document_uid int(11) unsigned default '0',
  object_identifier varchar(255) DEFAULT '' NOT NULL,

  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
  starttime int(11) unsigned DEFAULT '0' NOT NULL,
  endtime int(11) unsigned DEFAULT '0' NOT NULL,

  t3ver_oid int(11) DEFAULT '0' NOT NULL,
  t3ver_id int(11) DEFAULT '0' NOT NULL,
  t3ver_wsid int(11) DEFAULT '0' NOT NULL,
  t3ver_label varchar(255) DEFAULT '' NOT NULL,
  t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
  t3ver_stage int(11) DEFAULT '0' NOT NULL,
  t3ver_count int(11) DEFAULT '0' NOT NULL,
  t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
  t3ver_move_id int(11) DEFAULT '0' NOT NULL,

  sys_language_uid int(11) DEFAULT '0' NOT NULL,
  l10n_parent int(11) DEFAULT '0' NOT NULL,
  l10n_diffsource mediumblob,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid,t3ver_wsid),
  KEY language (l10n_parent,sys_language_uid)

);

#
# Table structure for table 'tx_dpf_domain_model_file'
#
CREATE TABLE tx_dpf_domain_model_file (

  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,

  title varchar(255) DEFAULT '' NOT NULL,
  label varchar(255) DEFAULT '' NOT NULL,
  download tinyint(4) unsigned DEFAULT '0' NOT NULL,
  archive tinyint(4) unsigned DEFAULT '0' NOT NULL,
  content_type varchar(255) DEFAULT '' NOT NULL,
  link varchar(255) DEFAULT '' NOT NULL,
  status varchar(255) DEFAULT '' NOT NULL,
  datastream_identifier varchar(255) DEFAULT '' NOT NULL,
  primary_file tinyint(4) unsigned DEFAULT '0' NOT NULL,
  document int(11) unsigned default '0',

  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
  starttime int(11) unsigned DEFAULT '0' NOT NULL,
  endtime int(11) unsigned DEFAULT '0' NOT NULL,

  t3ver_oid int(11) DEFAULT '0' NOT NULL,
  t3ver_id int(11) DEFAULT '0' NOT NULL,
  t3ver_wsid int(11) DEFAULT '0' NOT NULL,
  t3ver_label varchar(255) DEFAULT '' NOT NULL,
  t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
  t3ver_stage int(11) DEFAULT '0' NOT NULL,
  t3ver_count int(11) DEFAULT '0' NOT NULL,
  t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
  t3ver_move_id int(11) DEFAULT '0' NOT NULL,

  sys_language_uid int(11) DEFAULT '0' NOT NULL,
  l10n_parent int(11) DEFAULT '0' NOT NULL,
  l10n_diffsource mediumblob,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid,t3ver_wsid),
  KEY language (l10n_parent,sys_language_uid)

);

#
# Table structure for table 'tx_dpf_domain_model_fedoraconnection'
#
CREATE TABLE tx_dpf_domain_model_fedoraconnection (

  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,

  url varchar(255) DEFAULT '' NOT NULL,
  user varchar(255) DEFAULT '' NOT NULL,
  password varchar(255) DEFAULT '' NOT NULL,

  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
  starttime int(11) unsigned DEFAULT '0' NOT NULL,
  endtime int(11) unsigned DEFAULT '0' NOT NULL,

  t3ver_oid int(11) DEFAULT '0' NOT NULL,
  t3ver_id int(11) DEFAULT '0' NOT NULL,
  t3ver_wsid int(11) DEFAULT '0' NOT NULL,
  t3ver_label varchar(255) DEFAULT '' NOT NULL,
  t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
  t3ver_stage int(11) DEFAULT '0' NOT NULL,
  t3ver_count int(11) DEFAULT '0' NOT NULL,
  t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
  t3ver_move_id int(11) DEFAULT '0' NOT NULL,

  sys_language_uid int(11) DEFAULT '0' NOT NULL,
  l10n_parent int(11) DEFAULT '0' NOT NULL,
  l10n_diffsource mediumblob,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid,t3ver_wsid),
  KEY language (l10n_parent,sys_language_uid)

);

#
# Table structure for table 'tx_dpf_domain_model_metadatapage'
#
CREATE TABLE tx_dpf_domain_model_metadatapage (

  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,

  documenttype int(11) unsigned DEFAULT '0' NOT NULL,

  name varchar(255) DEFAULT '' NOT NULL,
  display_name varchar(255) DEFAULT '' NOT NULL,
  page_number int(11) DEFAULT '0' NOT NULL,
  metadata_group int(11) unsigned DEFAULT '0' NOT NULL,
  backend_only tinyint(1) unsigned DEFAULT '0' NOT NULL,

  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
  starttime int(11) unsigned DEFAULT '0' NOT NULL,
  endtime int(11) unsigned DEFAULT '0' NOT NULL,

  t3ver_oid int(11) DEFAULT '0' NOT NULL,
  t3ver_id int(11) DEFAULT '0' NOT NULL,
  t3ver_wsid int(11) DEFAULT '0' NOT NULL,
  t3ver_label varchar(255) DEFAULT '' NOT NULL,
  t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
  t3ver_stage int(11) DEFAULT '0' NOT NULL,
  t3ver_count int(11) DEFAULT '0' NOT NULL,
  t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
  t3ver_move_id int(11) DEFAULT '0' NOT NULL,

  sys_language_uid int(11) DEFAULT '0' NOT NULL,
  l10n_parent int(11) DEFAULT '0' NOT NULL,
  l10n_diffsource mediumblob,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid,t3ver_wsid),
  KEY language (l10n_parent,sys_language_uid)

);

#
# Table structure for table 'tx_dpf_metadatapage_metadatagroup_mm'
#
CREATE TABLE tx_dpf_metadatapage_metadatagroup_mm (
  uid_local int(11) unsigned DEFAULT '0' NOT NULL,
  uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
  sorting int(11) unsigned DEFAULT '0' NOT NULL,
  sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_dpf_domain_model_client'
#
CREATE TABLE tx_dpf_domain_model_client (

  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,

  project varchar(255) DEFAULT '' NOT NULL,
  client varchar(255) DEFAULT '' NOT NULL,
  network_initial varchar(255) DEFAULT '' NOT NULL,
  library_identifier varchar(255) DEFAULT '' NOT NULL,
  owner_id varchar(255) DEFAULT '' NOT NULL,
  admin_email varchar(255) DEFAULT '' NOT NULL,
  replace_niss_part tinyint(1) unsigned DEFAULT '0' NOT NULL,
  niss_part_search varchar(255) DEFAULT '' NOT NULL,
  niss_part_replace varchar(255) DEFAULT '' NOT NULL,

  sword_host varchar(255) DEFAULT '' NOT NULL,
  sword_user varchar(255) DEFAULT '' NOT NULL,
  sword_password varchar(255) DEFAULT '' NOT NULL,
  sword_collection_namespace varchar(255) DEFAULT '' NOT NULL,
  fedora_host varchar(255) DEFAULT '' NOT NULL,
  fedora_user varchar(255) DEFAULT '' NOT NULL,
  fedora_password varchar(255) DEFAULT '' NOT NULL,
  elastic_search_host varchar(255) DEFAULT '' NOT NULL,
  elastic_search_port varchar(255) DEFAULT '' NOT NULL,
  upload_directory varchar(255) DEFAULT '' NOT NULL,
  upload_domain varchar(255) DEFAULT '' NOT NULL,
  admin_new_document_notification_subject varchar(1024) DEFAULT '' NOT NULL,
  admin_new_document_notification_body text NOT NULL,
  submitter_new_document_notification_subject varchar(1024) DEFAULT '' NOT NULL,
  submitter_new_document_notification_body text NOT NULL,
  submitter_ingest_notification_subject varchar(1024) DEFAULT '' NOT NULL,
  submitter_ingest_notification_body text NOT NULL,

  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
  starttime int(11) unsigned DEFAULT '0' NOT NULL,
  endtime int(11) unsigned DEFAULT '0' NOT NULL,

  t3ver_oid int(11) DEFAULT '0' NOT NULL,
  t3ver_id int(11) DEFAULT '0' NOT NULL,
  t3ver_wsid int(11) DEFAULT '0' NOT NULL,
  t3ver_label varchar(255) DEFAULT '' NOT NULL,
  t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
  t3ver_stage int(11) DEFAULT '0' NOT NULL,
  t3ver_count int(11) DEFAULT '0' NOT NULL,
  t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
  t3ver_move_id int(11) DEFAULT '0' NOT NULL,

  sys_language_uid int(11) DEFAULT '0' NOT NULL,
  l10n_parent int(11) DEFAULT '0' NOT NULL,
  l10n_diffsource mediumblob,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid,t3ver_wsid),
  KEY language (l10n_parent,sys_language_uid)

);

#
# Table structure for table 'tx_dpf_domain_model_inputoptionlist'
#
CREATE TABLE tx_dpf_domain_model_inputoptionlist (

  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,

  metadataObject int(11) unsigned DEFAULT '0' NOT NULL,

  name varchar(255) DEFAULT '' NOT NULL,
  display_name varchar(255) DEFAULT '' NOT NULL,
  value_list text NOT NULL,
  additional_value_list text NOT NULL,
  value_label_list text NOT NULL,
  default_value text NOT NULL,

  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
  starttime int(11) unsigned DEFAULT '0' NOT NULL,
  endtime int(11) unsigned DEFAULT '0' NOT NULL,

  t3ver_oid int(11) DEFAULT '0' NOT NULL,
  t3ver_id int(11) DEFAULT '0' NOT NULL,
  t3ver_wsid int(11) DEFAULT '0' NOT NULL,
  t3ver_label varchar(255) DEFAULT '' NOT NULL,
  t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
  t3ver_stage int(11) DEFAULT '0' NOT NULL,
  t3ver_count int(11) DEFAULT '0' NOT NULL,
  t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
  t3ver_move_id int(11) DEFAULT '0' NOT NULL,
  sorting int(11) DEFAULT '0' NOT NULL,

  sys_language_uid int(11) DEFAULT '0' NOT NULL,
  l10n_parent int(11) DEFAULT '0' NOT NULL,
  l10n_diffsource mediumblob,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid,t3ver_wsid),
  KEY language (l10n_parent,sys_language_uid)

);

#
# Table structure for table 'tx_dpf_domain_model_processnumber'
#
CREATE TABLE tx_dpf_domain_model_processnumber (

  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,

  owner_id varchar(255) DEFAULT '' NOT NULL,
  year int(11) unsigned DEFAULT '0' NOT NULL,
  counter int(11) unsigned DEFAULT '0' NOT NULL,

  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
  starttime int(11) unsigned DEFAULT '0' NOT NULL,
  endtime int(11) unsigned DEFAULT '0' NOT NULL,

  t3ver_oid int(11) DEFAULT '0' NOT NULL,
  t3ver_id int(11) DEFAULT '0' NOT NULL,
  t3ver_wsid int(11) DEFAULT '0' NOT NULL,
  t3ver_label varchar(255) DEFAULT '' NOT NULL,
  t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
  t3ver_stage int(11) DEFAULT '0' NOT NULL,
  t3ver_count int(11) DEFAULT '0' NOT NULL,
  t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
  t3ver_move_id int(11) DEFAULT '0' NOT NULL,

  sys_language_uid int(11) DEFAULT '0' NOT NULL,
  l10n_parent int(11) DEFAULT '0' NOT NULL,
  l10n_diffsource mediumblob,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid,t3ver_wsid),
  KEY language (l10n_parent,sys_language_uid)

) ENGINE=InnoDB;
