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
  object_identifier varchar(255) DEFAULT NULL,
  reserved_object_identifier varchar(255) DEFAULT '' NOT NULL,
  process_number varchar(255) DEFAULT '' NOT NULL,
  state varchar(255) DEFAULT '' NOT NULL,
  remote_last_mod_date varchar(255) DEFAULT '' NOT NULL,
  transfer_status varchar(255) DEFAULT '' NOT NULL,
  transfer_date int(11) DEFAULT '0' NOT NULL,
  date_issued varchar(255) DEFAULT '' NOT NULL,
  changed tinyint(1) unsigned DEFAULT '0' NOT NULL,
  valid tinyint(1) unsigned DEFAULT '0' NOT NULL,
  embargo_date int(11) unsigned DEFAULT '0' NOT NULL,

  file int(11) unsigned DEFAULT '0' NOT NULL,
  owner int(11) unsigned default '0' NOT NULL,
  creator int(11) unsigned default '0' NOT NULL,
  temporary tinyint(1) unsigned DEFAULT '0' NOT NULL,
  suggestion tinyint(1) unsigned DEFAULT '0' NOT NULL,
  linked_uid varchar(255) DEFAULT '' NOT NULL,
  comment varchar(1024) DEFAULT '' NOT NULL,

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
  KEY language (l10n_parent,sys_language_uid),

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
  access_restriction_roles varchar(255) DEFAULT '' NOT NULL,
  mandatory varchar(255) DEFAULT '' NOT NULL,
  max_iteration int(11) DEFAULT '0' NOT NULL,
  mapping_for_reading varchar(1024) DEFAULT '' NOT NULL,
  mapping varchar(1024) DEFAULT '' NOT NULL,
  mods_extension_mapping varchar(1024) DEFAULT '' NOT NULL,
  mods_extension_reference varchar(1024) DEFAULT '' NOT NULL,
  info_text text NOT NULL,
  metadata_object int(11) unsigned DEFAULT '0' NOT NULL,
  group_type varchar(50) DEFAULT '' NOT NULL,

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
  mandatory varchar(255) DEFAULT '' NOT NULL,
  data_type varchar(255) DEFAULT '' NOT NULL,
  validation varchar(255) DEFAULT '' NOT NULL,
  mapping varchar(255) DEFAULT '' NOT NULL,
  mods_extension tinyint(1) unsigned DEFAULT '0' NOT NULL,
  input_field int(11) DEFAULT '0' NOT NULL,
  input_option_list int(11) unsigned default '0',
  default_value text NOT NULL,
  fill_out_service varchar(255) DEFAULT '' NOT NULL,
  backend_only tinyint(1) unsigned DEFAULT '0' NOT NULL,
  access_restriction_roles varchar(255) DEFAULT '' NOT NULL,
  consent tinyint(1) unsigned DEFAULT '0' NOT NULL,
  gnd_field_uid varchar(255) DEFAULT '' NOT NULL,
  max_input_length int(11) DEFAULT '0' NOT NULL,
  embargo tinyint(1) unsigned DEFAULT '0' NOT NULL,
  fis_person_mapping varchar(50) DEFAULT '' NOT NULL,
  fis_organisation_mapping varchar(50) DEFAULT '' NOT NULL,
  gnd_person_mapping varchar(50) DEFAULT '' NOT NULL,
  gnd_organisation_mapping varchar(50) DEFAULT '' NOT NULL,
  ror_mapping varchar(50) DEFAULT '' NOT NULL,
  zdb_mapping varchar(50) DEFAULT '' NOT NULL,
  unpaywall_mapping varchar(50) DEFAULT '' NOT NULL,
  orcid_person_mapping varchar(50) DEFAULT '' NOT NULL,
  object_type varchar(20) DEFAULT '' NOT NULL,

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
  file_group_deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
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
  access_restriction_roles varchar(255) DEFAULT '' NOT NULL,

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
  admin_register_document_notification_subject varchar(1024) DEFAULT '' NOT NULL,
  admin_register_document_notification_body text NOT NULL,
  admin_new_suggestion_subject varchar(1024) DEFAULT '' NOT NULL,
  admin_new_suggestion_body text NOT NULL,
  admin_embargo_subject varchar(1024) DEFAULT '' NOT NULL,
  admin_embargo_body text NOT NULL,
  admin_oa_fond_subject varchar(1024) DEFAULT '' NOT NULL,
  admin_oa_fond_body text NOT NULL,

  suggestion_flashmessage varchar(255) DEFAULT '' NOT NULL,

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

#
# Table structure for table 'tx_dpf_domain_model_editinglock'
#
CREATE TABLE tx_dpf_domain_model_editinglock (

  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,

  document_identifier varchar(255) DEFAULT '' NOT NULL,
  editor_uid int(11) unsigned default '0' NOT NULL,

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
  KEY language (l10n_parent,sys_language_uid),

  UNIQUE KEY uc_editinglock_document_identifier (document_identifier)

);

#
# Table extension structure for table 'fe_groups'
#
CREATE TABLE fe_groups (
  kitodo_role varchar(255) DEFAULT '' NOT NULL,
);

#
# Table extension structure for table 'fe_users'
#
CREATE TABLE fe_users (
  stored_searches int(11) DEFAULT '0' NOT NULL,
  notify_on_changes tinyint(4) unsigned DEFAULT '0' NOT NULL,
  notify_personal_link tinyint(4) unsigned DEFAULT '0' NOT NULL,
  notify_status_change tinyint(4) unsigned DEFAULT '0' NOT NULL,
  notify_fulltext_published tinyint(4) unsigned DEFAULT '0' NOT NULL,
  notify_new_publication_mypublication tinyint(4) unsigned DEFAULT '0' NOT NULL,
  fis_pers_id varchar(255) DEFAULT '' NOT NULL,
  orga_name varchar(255) DEFAULT '' NOT NULL,
);

#
# Table structure for table 'tx_dpf_domain_model_bookmark'
#
CREATE TABLE tx_dpf_domain_model_bookmark (

  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,

  document_identifier varchar(255) DEFAULT '' NOT NULL,
  fe_user_uid int(11) unsigned default '0' NOT NULL,

#  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
#  crdate int(11) unsigned DEFAULT '0' NOT NULL,
#  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
#  deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
#  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
#  starttime int(11) unsigned DEFAULT '0' NOT NULL,
#  endtime int(11) unsigned DEFAULT '0' NOT NULL,

#  t3ver_oid int(11) DEFAULT '0' NOT NULL,
#  t3ver_id int(11) DEFAULT '0' NOT NULL,
#  t3ver_wsid int(11) DEFAULT '0' NOT NULL,
#  t3ver_label varchar(255) DEFAULT '' NOT NULL,
#  t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
#  t3ver_stage int(11) DEFAULT '0' NOT NULL,
#  t3ver_count int(11) DEFAULT '0' NOT NULL,
#  t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
#  t3ver_move_id int(11) DEFAULT '0' NOT NULL,

#  sys_language_uid int(11) DEFAULT '0' NOT NULL,
#  l10n_parent int(11) DEFAULT '0' NOT NULL,
#  l10n_diffsource mediumblob,

  PRIMARY KEY (uid),
  KEY parent (pid),
#  KEY t3ver_oid (t3ver_oid,t3ver_wsid),
#  KEY language (l10n_parent,sys_language_uid),

  UNIQUE KEY uc_bookmark (fe_user_uid,document_identifier)

);

#
# Table structure for table 'tx_dpf_domain_model_storedsearch'
#
CREATE TABLE tx_dpf_domain_model_storedsearch (

  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,

  fe_user int(11) DEFAULT '0' NOT NULL,
  name varchar(1024) DEFAULT '' NOT NULL,
  query text DEFAULT '' NOT NULL,

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