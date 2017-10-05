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

  uid INT(11) NOT NULL AUTO_INCREMENT,
  pid INT(11) DEFAULT '0' NOT NULL,

  name VARCHAR(255) DEFAULT '' NOT NULL,
  display_name VARCHAR(255) DEFAULT '' NOT NULL,
  virtual TINYINT(1) UNSIGNED DEFAULT '0' NOT NULL,
  metadata_page INT(11) UNSIGNED DEFAULT '0' NOT NULL,

  tstamp INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  crdate INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  cruser_id INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  deleted TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  hidden TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  starttime INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  endtime INT(11) UNSIGNED DEFAULT '0' NOT NULL,

  t3ver_oid INT(11) DEFAULT '0' NOT NULL,
  t3ver_id INT(11) DEFAULT '0' NOT NULL,
  t3ver_wsid INT(11) DEFAULT '0' NOT NULL,
  t3ver_label VARCHAR(255) DEFAULT '' NOT NULL,
  t3ver_state TINYINT(4) DEFAULT '0' NOT NULL,
  t3ver_stage INT(11) DEFAULT '0' NOT NULL,
  t3ver_count INT(11) DEFAULT '0' NOT NULL,
  t3ver_tstamp INT(11) DEFAULT '0' NOT NULL,
  t3ver_move_id INT(11) DEFAULT '0' NOT NULL,

  sys_language_uid INT(11) DEFAULT '0' NOT NULL,
  l10n_parent INT(11) DEFAULT '0' NOT NULL,
  l10n_diffsource MEDIUMBLOB,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid, t3ver_wsid),
  KEY language (l10n_parent, sys_language_uid)

);

#
# Table structure for table 'tx_dpf_domain_model_document'
#
CREATE TABLE tx_dpf_domain_model_document (

  uid INT(11) NOT NULL AUTO_INCREMENT,
  pid INT(11) DEFAULT '0' NOT NULL,

  title VARCHAR(1024) DEFAULT '' NOT NULL,
  authors VARCHAR(1024) DEFAULT '' NOT NULL,
  metadata TEXT NOT NULL,
  xml_data TEXT NOT NULL,
  slub_info_data TEXT NOT NULL,
  document_type INT(11) UNSIGNED DEFAULT '0',
  object_identifier VARCHAR(255) DEFAULT '' NOT NULL,
  reserved_object_identifier VARCHAR(255) DEFAULT '' NOT NULL,
  process_number VARCHAR(255) DEFAULT '' NOT NULL,
  state VARCHAR(255) DEFAULT '' NOT NULL,
  transfer_status VARCHAR(255) DEFAULT '' NOT NULL,
  transfer_date INT(11) DEFAULT '0' NOT NULL,
  date_issued VARCHAR(255) DEFAULT '' NOT NULL,
  changed TINYINT(1) UNSIGNED DEFAULT '0' NOT NULL,
  valid TINYINT(1) UNSIGNED DEFAULT '0' NOT NULL,

  file INT(11) UNSIGNED DEFAULT '0' NOT NULL,

  tstamp INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  crdate INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  cruser_id INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  deleted TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  hidden TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  starttime INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  endtime INT(11) UNSIGNED DEFAULT '0' NOT NULL,

  t3ver_oid INT(11) DEFAULT '0' NOT NULL,
  t3ver_id INT(11) DEFAULT '0' NOT NULL,
  t3ver_wsid INT(11) DEFAULT '0' NOT NULL,
  t3ver_label VARCHAR(255) DEFAULT '' NOT NULL,
  t3ver_state TINYINT(4) DEFAULT '0' NOT NULL,
  t3ver_stage INT(11) DEFAULT '0' NOT NULL,
  t3ver_count INT(11) DEFAULT '0' NOT NULL,
  t3ver_tstamp INT(11) DEFAULT '0' NOT NULL,
  t3ver_move_id INT(11) DEFAULT '0' NOT NULL,

  sys_language_uid INT(11) DEFAULT '0' NOT NULL,
  l10n_parent INT(11) DEFAULT '0' NOT NULL,
  l10n_diffsource MEDIUMBLOB,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid, t3ver_wsid),
  KEY language (l10n_parent, sys_language_uid)

);

#
# Table structure for table 'tx_dpf_domain_model_metadatagroup'
#
CREATE TABLE tx_dpf_domain_model_metadatagroup (

  uid INT(11) NOT NULL AUTO_INCREMENT,
  pid INT(11) DEFAULT '0' NOT NULL,

  name VARCHAR(255) DEFAULT '' NOT NULL,
  display_name VARCHAR(255) DEFAULT '' NOT NULL,
  backend_only TINYINT(1) UNSIGNED DEFAULT '0' NOT NULL,
  mandatory TINYINT(1) UNSIGNED DEFAULT '0' NOT NULL,
  max_iteration INT(11) DEFAULT '0' NOT NULL,
  mapping_for_reading VARCHAR(1024) DEFAULT '' NOT NULL,
  mapping VARCHAR(1024) DEFAULT '' NOT NULL,
  mods_extension_mapping VARCHAR(1024) DEFAULT '' NOT NULL,
  mods_extension_reference VARCHAR(1024) DEFAULT '' NOT NULL,
  info_text TEXT NOT NULL,
  metadata_object INT(11) UNSIGNED DEFAULT '0' NOT NULL,

  tstamp INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  crdate INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  cruser_id INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  deleted TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  hidden TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  starttime INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  endtime INT(11) UNSIGNED DEFAULT '0' NOT NULL,

  t3ver_oid INT(11) DEFAULT '0' NOT NULL,
  t3ver_id INT(11) DEFAULT '0' NOT NULL,
  t3ver_wsid INT(11) DEFAULT '0' NOT NULL,
  t3ver_label VARCHAR(255) DEFAULT '' NOT NULL,
  t3ver_state TINYINT(4) DEFAULT '0' NOT NULL,
  t3ver_stage INT(11) DEFAULT '0' NOT NULL,
  t3ver_count INT(11) DEFAULT '0' NOT NULL,
  t3ver_tstamp INT(11) DEFAULT '0' NOT NULL,
  t3ver_move_id INT(11) DEFAULT '0' NOT NULL,

  sys_language_uid INT(11) DEFAULT '0' NOT NULL,
  l10n_parent INT(11) DEFAULT '0' NOT NULL,
  l10n_diffsource MEDIUMBLOB,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid, t3ver_wsid),
  KEY language (l10n_parent, sys_language_uid)

);

#
# Table structure for table 'tx_dpf_domain_model_metadataobject'
#
CREATE TABLE tx_dpf_domain_model_metadataobject (

  uid INT(11) NOT NULL AUTO_INCREMENT,
  pid INT(11) DEFAULT '0' NOT NULL,

  metadatagroup INT(11) UNSIGNED DEFAULT '0' NOT NULL,

  name VARCHAR(255) DEFAULT '' NOT NULL,
  display_name VARCHAR(255) DEFAULT '' NOT NULL,
  max_iteration INT(11) DEFAULT '0' NOT NULL,
  mandatory TINYINT(1) UNSIGNED DEFAULT '0' NOT NULL,
  data_type VARCHAR(255) DEFAULT '' NOT NULL,
  validation VARCHAR(255) DEFAULT '' NOT NULL,
  mapping VARCHAR(255) DEFAULT '' NOT NULL,
  mods_extension TINYINT(1) UNSIGNED DEFAULT '0' NOT NULL,
  input_field INT(11) DEFAULT '0' NOT NULL,
  input_option_list INT(11) UNSIGNED DEFAULT '0',
  default_value TEXT NOT NULL,
  fill_out_service VARCHAR(255) DEFAULT '' NOT NULL,
  backend_only TINYINT(1) UNSIGNED DEFAULT '0' NOT NULL,
  consent TINYINT(1) UNSIGNED DEFAULT '0' NOT NULL,

  tstamp INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  crdate INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  cruser_id INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  deleted TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  hidden TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  starttime INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  endtime INT(11) UNSIGNED DEFAULT '0' NOT NULL,

  t3ver_oid INT(11) DEFAULT '0' NOT NULL,
  t3ver_id INT(11) DEFAULT '0' NOT NULL,
  t3ver_wsid INT(11) DEFAULT '0' NOT NULL,
  t3ver_label VARCHAR(255) DEFAULT '' NOT NULL,
  t3ver_state TINYINT(4) DEFAULT '0' NOT NULL,
  t3ver_stage INT(11) DEFAULT '0' NOT NULL,
  t3ver_count INT(11) DEFAULT '0' NOT NULL,
  t3ver_tstamp INT(11) DEFAULT '0' NOT NULL,
  t3ver_move_id INT(11) DEFAULT '0' NOT NULL,

  sys_language_uid INT(11) DEFAULT '0' NOT NULL,
  l10n_parent INT(11) DEFAULT '0' NOT NULL,
  l10n_diffsource MEDIUMBLOB,

  sorting INT(11) UNSIGNED DEFAULT '0' NOT NULL,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid, t3ver_wsid),
  KEY language (l10n_parent, sys_language_uid)

);

#
# Table structure for table 'tx_dpf_domain_model_documenttransferlog'
#
CREATE TABLE tx_dpf_domain_model_documenttransferlog (

  uid INT(11) NOT NULL AUTO_INCREMENT,
  pid INT(11) DEFAULT '0' NOT NULL,

  date INT(11) DEFAULT '0' NOT NULL,
  response TEXT NOT NULL,
  curl_error TEXT NOT NULL,
  action VARCHAR(255) DEFAULT '' NOT NULL,
  document_uid INT(11) UNSIGNED DEFAULT '0',
  object_identifier VARCHAR(255) DEFAULT '' NOT NULL,

  tstamp INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  crdate INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  cruser_id INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  deleted TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  hidden TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  starttime INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  endtime INT(11) UNSIGNED DEFAULT '0' NOT NULL,

  t3ver_oid INT(11) DEFAULT '0' NOT NULL,
  t3ver_id INT(11) DEFAULT '0' NOT NULL,
  t3ver_wsid INT(11) DEFAULT '0' NOT NULL,
  t3ver_label VARCHAR(255) DEFAULT '' NOT NULL,
  t3ver_state TINYINT(4) DEFAULT '0' NOT NULL,
  t3ver_stage INT(11) DEFAULT '0' NOT NULL,
  t3ver_count INT(11) DEFAULT '0' NOT NULL,
  t3ver_tstamp INT(11) DEFAULT '0' NOT NULL,
  t3ver_move_id INT(11) DEFAULT '0' NOT NULL,

  sys_language_uid INT(11) DEFAULT '0' NOT NULL,
  l10n_parent INT(11) DEFAULT '0' NOT NULL,
  l10n_diffsource MEDIUMBLOB,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid, t3ver_wsid),
  KEY language (l10n_parent, sys_language_uid)

);

#
# Table structure for table 'tx_dpf_domain_model_file'
#
CREATE TABLE tx_dpf_domain_model_file (

  uid INT(11) NOT NULL AUTO_INCREMENT,
  pid INT(11) DEFAULT '0' NOT NULL,

  title VARCHAR(255) DEFAULT '' NOT NULL,
  label VARCHAR(255) DEFAULT '' NOT NULL,
  download TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  archive TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  content_type VARCHAR(255) DEFAULT '' NOT NULL,
  link VARCHAR(255) DEFAULT '' NOT NULL,
  status VARCHAR(255) DEFAULT '' NOT NULL,
  datastream_identifier VARCHAR(255) DEFAULT '' NOT NULL,
  primary_file TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  document INT(11) UNSIGNED DEFAULT '0',

  tstamp INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  crdate INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  cruser_id INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  deleted TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  hidden TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  starttime INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  endtime INT(11) UNSIGNED DEFAULT '0' NOT NULL,

  t3ver_oid INT(11) DEFAULT '0' NOT NULL,
  t3ver_id INT(11) DEFAULT '0' NOT NULL,
  t3ver_wsid INT(11) DEFAULT '0' NOT NULL,
  t3ver_label VARCHAR(255) DEFAULT '' NOT NULL,
  t3ver_state TINYINT(4) DEFAULT '0' NOT NULL,
  t3ver_stage INT(11) DEFAULT '0' NOT NULL,
  t3ver_count INT(11) DEFAULT '0' NOT NULL,
  t3ver_tstamp INT(11) DEFAULT '0' NOT NULL,
  t3ver_move_id INT(11) DEFAULT '0' NOT NULL,

  sys_language_uid INT(11) DEFAULT '0' NOT NULL,
  l10n_parent INT(11) DEFAULT '0' NOT NULL,
  l10n_diffsource MEDIUMBLOB,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid, t3ver_wsid),
  KEY language (l10n_parent, sys_language_uid)

);

#
# Table structure for table 'tx_dpf_domain_model_fedoraconnection'
#
CREATE TABLE tx_dpf_domain_model_fedoraconnection (

  uid INT(11) NOT NULL AUTO_INCREMENT,
  pid INT(11) DEFAULT '0' NOT NULL,

  url VARCHAR(255) DEFAULT '' NOT NULL,
  user VARCHAR(255) DEFAULT '' NOT NULL,
  password VARCHAR(255) DEFAULT '' NOT NULL,

  tstamp INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  crdate INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  cruser_id INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  deleted TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  hidden TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  starttime INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  endtime INT(11) UNSIGNED DEFAULT '0' NOT NULL,

  t3ver_oid INT(11) DEFAULT '0' NOT NULL,
  t3ver_id INT(11) DEFAULT '0' NOT NULL,
  t3ver_wsid INT(11) DEFAULT '0' NOT NULL,
  t3ver_label VARCHAR(255) DEFAULT '' NOT NULL,
  t3ver_state TINYINT(4) DEFAULT '0' NOT NULL,
  t3ver_stage INT(11) DEFAULT '0' NOT NULL,
  t3ver_count INT(11) DEFAULT '0' NOT NULL,
  t3ver_tstamp INT(11) DEFAULT '0' NOT NULL,
  t3ver_move_id INT(11) DEFAULT '0' NOT NULL,

  sys_language_uid INT(11) DEFAULT '0' NOT NULL,
  l10n_parent INT(11) DEFAULT '0' NOT NULL,
  l10n_diffsource MEDIUMBLOB,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid, t3ver_wsid),
  KEY language (l10n_parent, sys_language_uid)

);

#
# Table structure for table 'tx_dpf_domain_model_metadatapage'
#
CREATE TABLE tx_dpf_domain_model_metadatapage (

  uid INT(11) NOT NULL AUTO_INCREMENT,
  pid INT(11) DEFAULT '0' NOT NULL,

  documenttype INT(11) UNSIGNED DEFAULT '0' NOT NULL,

  name VARCHAR(255) DEFAULT '' NOT NULL,
  display_name VARCHAR(255) DEFAULT '' NOT NULL,
  page_number INT(11) DEFAULT '0' NOT NULL,
  metadata_group INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  backend_only TINYINT(1) UNSIGNED DEFAULT '0' NOT NULL,

  tstamp INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  crdate INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  cruser_id INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  deleted TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  hidden TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  starttime INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  endtime INT(11) UNSIGNED DEFAULT '0' NOT NULL,

  t3ver_oid INT(11) DEFAULT '0' NOT NULL,
  t3ver_id INT(11) DEFAULT '0' NOT NULL,
  t3ver_wsid INT(11) DEFAULT '0' NOT NULL,
  t3ver_label VARCHAR(255) DEFAULT '' NOT NULL,
  t3ver_state TINYINT(4) DEFAULT '0' NOT NULL,
  t3ver_stage INT(11) DEFAULT '0' NOT NULL,
  t3ver_count INT(11) DEFAULT '0' NOT NULL,
  t3ver_tstamp INT(11) DEFAULT '0' NOT NULL,
  t3ver_move_id INT(11) DEFAULT '0' NOT NULL,

  sys_language_uid INT(11) DEFAULT '0' NOT NULL,
  l10n_parent INT(11) DEFAULT '0' NOT NULL,
  l10n_diffsource MEDIUMBLOB,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid, t3ver_wsid),
  KEY language (l10n_parent, sys_language_uid)

);

#
# Table structure for table 'tx_dpf_domain_model_metadatapage'
#
CREATE TABLE tx_dpf_domain_model_metadatapage (

  documenttype INT(11) UNSIGNED DEFAULT '0' NOT NULL,

);

#
# Table structure for table 'tx_dpf_domain_model_metadataobject'
#
CREATE TABLE tx_dpf_domain_model_metadataobject (

  metadatagroup INT(11) UNSIGNED DEFAULT '0' NOT NULL,

);

#
# Table structure for table 'tx_dpf_metadatapage_metadatagroup_mm'
#
CREATE TABLE tx_dpf_metadatapage_metadatagroup_mm (
  uid_local INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  uid_foreign INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  sorting INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  sorting_foreign INT(11) UNSIGNED DEFAULT '0' NOT NULL,

  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_dpf_domain_model_client'
#
CREATE TABLE tx_dpf_domain_model_client (

  uid INT(11) NOT NULL AUTO_INCREMENT,
  pid INT(11) DEFAULT '0' NOT NULL,

  project VARCHAR(255) DEFAULT '' NOT NULL,
  client VARCHAR(255) DEFAULT '' NOT NULL,
  network_initial VARCHAR(255) DEFAULT '' NOT NULL,
  library_identifier VARCHAR(255) DEFAULT '' NOT NULL,
  owner_id VARCHAR(255) DEFAULT '' NOT NULL,
  admin_email VARCHAR(255) DEFAULT '' NOT NULL,
  replace_niss_part TINYINT(1) UNSIGNED DEFAULT '0' NOT NULL,
  niss_part_search VARCHAR(255) DEFAULT '' NOT NULL,
  niss_part_replace VARCHAR(255) DEFAULT '' NOT NULL,

  sword_host VARCHAR(255) DEFAULT '' NOT NULL,
  sword_user VARCHAR(255) DEFAULT '' NOT NULL,
  sword_password VARCHAR(255) DEFAULT '' NOT NULL,
  sword_collection_namespace VARCHAR(255) DEFAULT '' NOT NULL,
  fedora_host VARCHAR(255) DEFAULT '' NOT NULL,
  fedora_user VARCHAR(255) DEFAULT '' NOT NULL,
  fedora_password VARCHAR(255) DEFAULT '' NOT NULL,
  elastic_search_host VARCHAR(255) DEFAULT '' NOT NULL,
  elastic_search_port VARCHAR(255) DEFAULT '' NOT NULL,
  upload_directory VARCHAR(255) DEFAULT '' NOT NULL,
  upload_domain VARCHAR(255) DEFAULT '' NOT NULL,
  admin_new_document_notification_subject VARCHAR(1024) DEFAULT '' NOT NULL,
  admin_new_document_notification_body TEXT NOT NULL,
  submitter_new_document_notification_subject VARCHAR(1024) DEFAULT '' NOT NULL,
  submitter_new_document_notification_body TEXT NOT NULL,
  submitter_ingest_notification_subject VARCHAR(1024) DEFAULT '' NOT NULL,
  submitter_ingest_notification_body TEXT NOT NULL,

  tstamp INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  crdate INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  cruser_id INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  deleted TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  hidden TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  starttime INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  endtime INT(11) UNSIGNED DEFAULT '0' NOT NULL,

  t3ver_oid INT(11) DEFAULT '0' NOT NULL,
  t3ver_id INT(11) DEFAULT '0' NOT NULL,
  t3ver_wsid INT(11) DEFAULT '0' NOT NULL,
  t3ver_label VARCHAR(255) DEFAULT '' NOT NULL,
  t3ver_state TINYINT(4) DEFAULT '0' NOT NULL,
  t3ver_stage INT(11) DEFAULT '0' NOT NULL,
  t3ver_count INT(11) DEFAULT '0' NOT NULL,
  t3ver_tstamp INT(11) DEFAULT '0' NOT NULL,
  t3ver_move_id INT(11) DEFAULT '0' NOT NULL,

  sys_language_uid INT(11) DEFAULT '0' NOT NULL,
  l10n_parent INT(11) DEFAULT '0' NOT NULL,
  l10n_diffsource MEDIUMBLOB,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid, t3ver_wsid),
  KEY language (l10n_parent, sys_language_uid)

);

#
# Table structure for table 'tx_dpf_domain_model_inputoptionlist'
#
CREATE TABLE tx_dpf_domain_model_inputoptionlist (

  uid INT(11) NOT NULL AUTO_INCREMENT,
  pid INT(11) DEFAULT '0' NOT NULL,

  metadataObject INT(11) UNSIGNED DEFAULT '0' NOT NULL,

  name VARCHAR(255) DEFAULT '' NOT NULL,
  display_name VARCHAR(255) DEFAULT '' NOT NULL,
  value_list TEXT NOT NULL,
  value_label_list TEXT NOT NULL,
  default_value TEXT NOT NULL,

  tstamp INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  crdate INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  cruser_id INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  deleted TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  hidden TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  starttime INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  endtime INT(11) UNSIGNED DEFAULT '0' NOT NULL,

  t3ver_oid INT(11) DEFAULT '0' NOT NULL,
  t3ver_id INT(11) DEFAULT '0' NOT NULL,
  t3ver_wsid INT(11) DEFAULT '0' NOT NULL,
  t3ver_label VARCHAR(255) DEFAULT '' NOT NULL,
  t3ver_state TINYINT(4) DEFAULT '0' NOT NULL,
  t3ver_stage INT(11) DEFAULT '0' NOT NULL,
  t3ver_count INT(11) DEFAULT '0' NOT NULL,
  t3ver_tstamp INT(11) DEFAULT '0' NOT NULL,
  t3ver_move_id INT(11) DEFAULT '0' NOT NULL,
  sorting INT(11) DEFAULT '0' NOT NULL,

  sys_language_uid INT(11) DEFAULT '0' NOT NULL,
  l10n_parent INT(11) DEFAULT '0' NOT NULL,
  l10n_diffsource MEDIUMBLOB,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid, t3ver_wsid),
  KEY language (l10n_parent, sys_language_uid)

);

#
# Table structure for table 'tx_dpf_domain_model_processnumber'
#
CREATE TABLE tx_dpf_domain_model_processnumber (

  uid INT(11) NOT NULL AUTO_INCREMENT,
  pid INT(11) DEFAULT '0' NOT NULL,

  owner_id VARCHAR(255) DEFAULT '' NOT NULL,
  year INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  counter INT(11) UNSIGNED DEFAULT '0' NOT NULL,

  tstamp INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  crdate INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  cruser_id INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  deleted TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  hidden TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  starttime INT(11) UNSIGNED DEFAULT '0' NOT NULL,
  endtime INT(11) UNSIGNED DEFAULT '0' NOT NULL,

  t3ver_oid INT(11) DEFAULT '0' NOT NULL,
  t3ver_id INT(11) DEFAULT '0' NOT NULL,
  t3ver_wsid INT(11) DEFAULT '0' NOT NULL,
  t3ver_label VARCHAR(255) DEFAULT '' NOT NULL,
  t3ver_state TINYINT(4) DEFAULT '0' NOT NULL,
  t3ver_stage INT(11) DEFAULT '0' NOT NULL,
  t3ver_count INT(11) DEFAULT '0' NOT NULL,
  t3ver_tstamp INT(11) DEFAULT '0' NOT NULL,
  t3ver_move_id INT(11) DEFAULT '0' NOT NULL,

  sys_language_uid INT(11) DEFAULT '0' NOT NULL,
  l10n_parent INT(11) DEFAULT '0' NOT NULL,
  l10n_diffsource MEDIUMBLOB,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid, t3ver_wsid),
  KEY language (l10n_parent, sys_language_uid)

) ENGINE=InnoDB;