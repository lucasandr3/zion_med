CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_expiration_index" on "cache"("expiration");
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_locks_expiration_index" on "cache_locks"("expiration");
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "form_fields"(
  "id" integer primary key autoincrement not null,
  "template_id" integer not null,
  "type" varchar not null,
  "label" varchar not null,
  "name_key" varchar not null,
  "required" tinyint(1) not null default '0',
  "options_json" text,
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("template_id") references "form_templates"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "submission_values"(
  "id" integer primary key autoincrement not null,
  "submission_id" integer not null,
  "field_id" integer,
  "key" varchar not null,
  "value_text" text,
  "value_json" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("submission_id") references "form_submissions"("id") on delete cascade,
  foreign key("field_id") references "form_fields"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "submission_attachments"(
  "id" integer primary key autoincrement not null,
  "submission_id" integer not null,
  "file_path" varchar not null,
  "original_name" varchar not null,
  "mime" varchar,
  "size" integer not null default '0',
  "field_key" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("submission_id") references "form_submissions"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "submission_events"(
  "id" integer primary key autoincrement not null,
  "form_submission_id" integer not null,
  "type" varchar not null,
  "user_id" integer,
  "body" text,
  "created_at" datetime,
  "updated_at" datetime,
  "meta_json" text,
  foreign key("form_submission_id") references "form_submissions"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "notifications"(
  "id" varchar not null,
  "type" varchar not null,
  "notifiable_type" varchar not null,
  "notifiable_id" integer not null,
  "data" text not null,
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  primary key("id")
);
CREATE INDEX "notifications_notifiable_type_notifiable_id_index" on "notifications"(
  "notifiable_type",
  "notifiable_id"
);
CREATE TABLE IF NOT EXISTS "personal_access_tokens"(
  "id" integer primary key autoincrement not null,
  "tokenable_type" varchar not null,
  "tokenable_id" integer not null,
  "name" text not null,
  "token" varchar not null,
  "abilities" text,
  "last_used_at" datetime,
  "expires_at" datetime,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "personal_access_tokens_tokenable_type_tokenable_id_index" on "personal_access_tokens"(
  "tokenable_type",
  "tokenable_id"
);
CREATE UNIQUE INDEX "personal_access_tokens_token_unique" on "personal_access_tokens"(
  "token"
);
CREATE INDEX "personal_access_tokens_expires_at_index" on "personal_access_tokens"(
  "expires_at"
);
CREATE TABLE IF NOT EXISTS "link_bio_link_clicks"(
  "id" integer primary key autoincrement not null,
  "clinic_link_id" integer not null,
  "date" date not null,
  "clicks" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("clinic_link_id") references "clinic_links"("id") on delete cascade
);
CREATE UNIQUE INDEX "link_bio_link_clicks_clinic_link_id_date_unique" on "link_bio_link_clicks"(
  "clinic_link_id",
  "date"
);
CREATE TABLE IF NOT EXISTS "webhook_deliveries"(
  "id" integer primary key autoincrement not null,
  "clinic_webhook_id" integer not null,
  "event" varchar not null,
  "payload" text not null,
  "response_code" integer,
  "response_body" text,
  "attempt" integer not null default '1',
  "delivered_at" datetime,
  "error_message" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("clinic_webhook_id") references "clinic_webhooks"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "tenants"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "slug" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "tenants_slug_unique" on "tenants"("slug");
CREATE TABLE IF NOT EXISTS "organizations"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "slug" varchar not null,
  "logo_path" varchar,
  "notification_email" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "theme" varchar not null default('zion-blue'),
  "dark_mode" tinyint(1) not null default('0'),
  "address" varchar,
  "business_hours" text,
  "phone" varchar,
  "contact_email" varchar,
  "short_description" varchar,
  "specialties" varchar,
  "founded_year" integer,
  "meta_description" varchar,
  "cover_image_path" varchar,
  "maps_url" varchar,
  "asaas_customer_id" varchar,
  "billing_email" varchar,
  "billing_name" varchar,
  "billing_document" varchar,
  "plan_key" varchar,
  "trial_ends_at" datetime,
  "grace_ends_at" datetime,
  "subscription_status" varchar,
  "billing_status" varchar,
  "whatsapp_notifications_enabled" tinyint(1) not null default('0'),
  "whatsapp_notify_cobranca" tinyint(1) not null default('1'),
  "whatsapp_notify_faturas_boleto" tinyint(1) not null default('1'),
  "whatsapp_notify_avisos" tinyint(1) not null default('1'),
  "tenant_id" integer,
  "public_theme" varchar,
  "cover_color" varchar,
  "signing_security_level" varchar not null default 'basic',
  "cover_mode" varchar,
  "link_bio_model" integer not null default '1',
  "link_bio_extra" text,
  "evolution_go_instance_name" varchar,
  "evolution_go_remote_id" varchar,
  "evolution_go_instance_token" text,
  "feegow_enabled" tinyint(1) not null default '0',
  "feegow_base_url" varchar,
  "feegow_token" text,
  "feegow_last_check_at" datetime,
  "feegow_last_status" varchar,
  "feegow_last_error" text,
  "data_retention_years" integer,
  "accent_hex" varchar,
  "professional_photo_path" varchar,
  "niche" varchar not null default 'estetica',
  "google_place_id" varchar,
  "google_reviews_enabled" tinyint(1) not null default '0',
  foreign key("tenant_id") references "tenants"("id") on delete cascade
);
CREATE UNIQUE INDEX "clinics_slug_unique" on "organizations"("slug");
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "organization_id" integer,
  "role" varchar not null default('staff'),
  "active" tinyint(1) not null default('1'),
  "can_switch_clinic" tinyint(1) not null default('0'),
  "ui_theme" varchar,
  "ui_dark_mode" tinyint(1),
  "electronic_signature_path" varchar,
  "electronic_signature_updated_at" datetime,
  "ui_shell_preset" varchar,
  foreign key("organization_id") references "organizations"("id") on delete cascade
);
CREATE UNIQUE INDEX "users_organization_id_email_unique" on "users"(
  "organization_id",
  "email"
);
CREATE TABLE IF NOT EXISTS "form_templates"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "name" varchar not null,
  "description" text,
  "is_active" tinyint(1) not null default('1'),
  "public_enabled" tinyint(1) not null default('0'),
  "public_token" varchar,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "category" varchar,
  "public_token_expires_at" datetime,
  "public_require_person_link" tinyint(1) not null default '0',
  foreign key("created_by") references users("id") on delete set null on update no action,
  foreign key("organization_id") references "organizations"("id") on delete cascade
);
CREATE UNIQUE INDEX "form_templates_public_token_unique" on "form_templates"(
  "public_token"
);
CREATE TABLE IF NOT EXISTS "audit_logs"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer,
  "user_id" integer,
  "action" varchar not null,
  "entity_type" varchar,
  "entity_id" integer,
  "meta_json" text,
  "created_at" datetime not null,
  foreign key("user_id") references users("id") on delete set null on update no action,
  foreign key("organization_id") references "organizations"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "protocol_sequences"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "year" integer not null,
  "last_number" integer not null default('0'),
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("organization_id") references "organizations"("id") on delete cascade
);
CREATE UNIQUE INDEX "protocol_sequences_clinic_id_year_unique" on "protocol_sequences"(
  "organization_id",
  "year"
);
CREATE TABLE IF NOT EXISTS "subscriptions"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "asaas_subscription_id" varchar,
  "plan_key" varchar,
  "status" varchar not null,
  "current_period_end" date,
  "next_due_date" date,
  "created_at" datetime,
  "updated_at" datetime,
  "billing_type" varchar not null default 'BOLETO',
  foreign key("organization_id") references "organizations"("id") on delete cascade
);
CREATE UNIQUE INDEX "subscriptions_asaas_subscription_id_unique" on "subscriptions"(
  "asaas_subscription_id"
);
CREATE TABLE IF NOT EXISTS "payments"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "subscription_id" integer,
  "asaas_payment_id" varchar,
  "status" varchar not null,
  "due_date" date,
  "paid_at" datetime,
  "value" numeric,
  "created_at" datetime,
  "updated_at" datetime,
  "bank_slip_url" varchar,
  "pix_qr_encoded_image" text,
  "pix_copy_paste" text,
  foreign key("subscription_id") references subscriptions("id") on delete set null on update no action,
  foreign key("organization_id") references "organizations"("id") on delete cascade
);
CREATE UNIQUE INDEX "payments_asaas_payment_id_unique" on "payments"(
  "asaas_payment_id"
);
CREATE TABLE IF NOT EXISTS "link_bio_page_views"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "date" date not null,
  "views" integer not null default('0'),
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("organization_id") references "organizations"("id") on delete cascade
);
CREATE UNIQUE INDEX "link_bio_page_views_clinic_id_date_unique" on "link_bio_page_views"(
  "organization_id",
  "date"
);
CREATE TABLE IF NOT EXISTS "clinic_links"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "label" varchar not null,
  "url" varchar not null,
  "icon" varchar not null default('link'),
  "sort_order" integer not null default('0'),
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("organization_id") references "organizations"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "clinic_webhooks"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "url" varchar not null,
  "secret" varchar,
  "events" text not null,
  "is_active" tinyint(1) not null default('1'),
  "description" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("organization_id") references "organizations"("id") on delete cascade
);
CREATE INDEX "form_templates_organization_id_index" on "form_templates"(
  "organization_id"
);
CREATE INDEX "form_templates_created_at_index" on "form_templates"(
  "created_at"
);
CREATE INDEX "submission_values_submission_id_index" on "submission_values"(
  "submission_id"
);
CREATE INDEX "submission_attachments_submission_id_index" on "submission_attachments"(
  "submission_id"
);
CREATE INDEX "audit_logs_organization_id_index" on "audit_logs"(
  "organization_id"
);
CREATE INDEX "audit_logs_created_at_index" on "audit_logs"("created_at");
CREATE TABLE IF NOT EXISTS "demonstration_requests"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "clinic" varchar not null,
  "email" varchar not null,
  "phone" varchar not null,
  "message" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "platform_settings"(
  "key" varchar not null,
  "value" text,
  "created_at" datetime,
  "updated_at" datetime,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "plans"(
  "id" integer primary key autoincrement not null,
  "key" varchar not null,
  "name" varchar not null,
  "value" numeric not null default '0',
  "description" text,
  "sort_order" integer not null default '0',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  "max_users" integer,
  "max_organizations_per_tenant" integer
);
CREATE UNIQUE INDEX "plans_key_unique" on "plans"("key");
CREATE TABLE IF NOT EXISTS "form_template_versions"(
  "id" integer primary key autoincrement not null,
  "form_template_id" integer not null,
  "version" integer not null default '1',
  "name" varchar not null,
  "description" text,
  "fields_snapshot" text not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("form_template_id") references "form_templates"("id") on delete cascade
);
CREATE UNIQUE INDEX "form_template_versions_form_template_id_version_unique" on "form_template_versions"(
  "form_template_id",
  "version"
);
CREATE TABLE IF NOT EXISTS "submission_signatures"(
  "id" integer primary key autoincrement not null,
  "submission_id" integer not null,
  "image_path" varchar not null,
  "field_key" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "signed_name" varchar,
  "signed_ip" varchar,
  "signed_user_agent" varchar,
  "signed_hash" varchar,
  "signed_at" datetime,
  "form_template_version_id" integer,
  "document_hash" varchar,
  "evidence_hash" varchar,
  "channel" varchar not null default 'web',
  "status" varchar not null default 'completed',
  "accepted_text_at" datetime,
  "locale" varchar,
  "timezone" varchar,
  foreign key("submission_id") references form_submissions("id") on delete cascade on update no action,
  foreign key("form_template_version_id") references "form_template_versions"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "otp_challenges"(
  "id" integer primary key autoincrement not null,
  "token" varchar not null,
  "channel" varchar not null,
  "recipient" varchar not null,
  "code" varchar not null,
  "expires_at" datetime not null,
  "verified_at" datetime,
  "attempts" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "otp_challenges_token_channel_index" on "otp_challenges"(
  "token",
  "channel"
);
CREATE INDEX "otp_challenges_expires_at_index" on "otp_challenges"(
  "expires_at"
);
CREATE TABLE IF NOT EXISTS "people"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "code" varchar not null,
  "name" varchar not null,
  "phone" varchar,
  "email" varchar,
  "birth_date" date,
  "cpf" varchar,
  "notes" text,
  "status" varchar not null default 'active',
  "created_at" datetime,
  "updated_at" datetime,
  "cpf_hash" varchar,
  "email_hash" varchar,
  "phone_alt" varchar,
  "age" integer,
  "sex" varchar,
  "rg" varchar,
  "marital_status" varchar,
  "profession" varchar,
  "referred_by" varchar,
  "address" varchar,
  "neighborhood" varchar,
  "city" varchar,
  "cep" varchar,
  "lead_source_instagram" tinyint(1) not null default '0',
  "lead_source_google" tinyint(1) not null default '0',
  "lead_source_facebook" tinyint(1) not null default '0',
  "lead_source_indicacao_amigo" tinyint(1) not null default '0',
  "lead_source_indicacao_medica" tinyint(1) not null default '0',
  "lead_source_plano_saude" tinyint(1) not null default '0',
  "lead_source_outro" varchar,
  "has_health_plan" varchar,
  "health_plan_operator" varchar,
  "health_plan_card_number" varchar,
  "lgpd_accept_comms" tinyint(1) not null default '0',
  "lgpd_accept_reminders" tinyint(1) not null default '0',
  foreign key("organization_id") references "organizations"("id") on delete cascade
);
CREATE UNIQUE INDEX "people_organization_id_code_unique" on "people"(
  "organization_id",
  "code"
);
CREATE INDEX "people_organization_id_name_index" on "people"(
  "organization_id",
  "name"
);
CREATE INDEX "people_organization_id_phone_index" on "people"(
  "organization_id",
  "phone"
);
CREATE INDEX "people_organization_id_email_index" on "people"(
  "organization_id",
  "email"
);
CREATE TABLE IF NOT EXISTS "form_submissions"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "template_id" integer not null,
  "status" varchar not null default('pending'),
  "submitted_by_user_id" integer,
  "submitter_name" varchar,
  "submitter_email" varchar,
  "submitted_at" datetime,
  "approved_by_user_id" integer,
  "approved_at" datetime,
  "review_comment" text,
  "protocol_number" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "template_version_id" integer,
  "document_hash" varchar,
  "document_snapshot_hash" varchar,
  "signing_channel" varchar not null default('web'),
  "signing_status" varchar not null default('completed'),
  "locale" varchar,
  "timezone" varchar,
  "accepted_text_at" datetime,
  "person_id" integer,
  foreign key("template_version_id") references form_template_versions("id") on delete set null on update no action,
  foreign key("template_id") references form_templates("id") on delete cascade on update no action,
  foreign key("submitted_by_user_id") references users("id") on delete set null on update no action,
  foreign key("approved_by_user_id") references users("id") on delete set null on update no action,
  foreign key("organization_id") references organizations("id") on delete cascade on update no action,
  foreign key("person_id") references "people"("id") on delete set null
);
CREATE INDEX "form_submissions_created_at_index" on "form_submissions"(
  "created_at"
);
CREATE INDEX "form_submissions_organization_id_index" on "form_submissions"(
  "organization_id"
);
CREATE UNIQUE INDEX "form_submissions_protocol_number_unique" on "form_submissions"(
  "protocol_number"
);
CREATE INDEX "form_submissions_status_index" on "form_submissions"("status");
CREATE INDEX "form_submissions_template_id_index" on "form_submissions"(
  "template_id"
);
CREATE INDEX "form_submissions_organization_id_person_id_index" on "form_submissions"(
  "organization_id",
  "person_id"
);
CREATE TABLE IF NOT EXISTS "document_sends"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "form_template_id" integer not null,
  "recipient_email" varchar,
  "recipient_phone" varchar,
  "channel" varchar not null,
  "sent_at" datetime not null,
  "expires_at" datetime,
  "form_submission_id" integer,
  "public_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "cancelled_at" datetime,
  "reminded_at" datetime,
  "person_id" integer,
  "recipient_name" varchar,
  foreign key("form_submission_id") references form_submissions("id") on delete set null on update no action,
  foreign key("form_template_id") references form_templates("id") on delete cascade on update no action,
  foreign key("organization_id") references organizations("id") on delete cascade on update no action,
  foreign key("person_id") references "people"("id") on delete set null
);
CREATE INDEX "document_sends_form_template_id_sent_at_index" on "document_sends"(
  "form_template_id",
  "sent_at"
);
CREATE INDEX "document_sends_organization_id_channel_index" on "document_sends"(
  "organization_id",
  "channel"
);
CREATE TABLE IF NOT EXISTS "organization_roles"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "slug" varchar not null,
  "label" varchar not null,
  "is_system" tinyint(1) not null default '0',
  "is_assignable" tinyint(1) not null default '1',
  "permissions" text not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("organization_id") references "organizations"("id") on delete cascade
);
CREATE UNIQUE INDEX "organization_roles_organization_id_slug_unique" on "organization_roles"(
  "organization_id",
  "slug"
);
CREATE TABLE IF NOT EXISTS "organization_presences"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "organization_name" varchar not null,
  "active_sessions" integer not null default '0',
  "last_seen_at" datetime,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "organization_presences_organization_id_unique" on "organization_presences"(
  "organization_id"
);
CREATE TABLE IF NOT EXISTS "feegow_appointments"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "person_id" integer,
  "feegow_appointment_id" integer not null,
  "status" varchar not null default 'created',
  "request_payload" text,
  "response_payload" text,
  "external_reference" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("organization_id") references "organizations"("id") on delete cascade,
  foreign key("person_id") references "people"("id") on delete set null
);
CREATE INDEX "feegow_appointments_organization_id_person_id_index" on "feegow_appointments"(
  "organization_id",
  "person_id"
);
CREATE UNIQUE INDEX "feegow_appointments_organization_id_feegow_appointment_id_unique" on "feegow_appointments"(
  "organization_id",
  "feegow_appointment_id"
);
CREATE INDEX "feegow_appointments_organization_id_external_reference_index" on "feegow_appointments"(
  "organization_id",
  "external_reference"
);
CREATE TABLE IF NOT EXISTS "organization_addresses"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "cep" varchar,
  "logradouro" varchar,
  "numero" varchar,
  "complemento" varchar,
  "bairro" varchar,
  "cidade" varchar,
  "uf" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("organization_id") references "organizations"("id") on delete cascade
);
CREATE UNIQUE INDEX "organization_addresses_organization_id_unique" on "organization_addresses"(
  "organization_id"
);
CREATE TABLE IF NOT EXISTS "link_bio_cta_clicks"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "channel" varchar not null,
  "ref" varchar not null default '',
  "date" date not null,
  "clicks" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("organization_id") references "organizations"("id") on delete cascade
);
CREATE UNIQUE INDEX "link_bio_cta_clicks_org_channel_ref_date_unique" on "link_bio_cta_clicks"(
  "organization_id",
  "channel",
  "ref",
  "date"
);
CREATE INDEX "otp_challenges_token_recipient_index" on "otp_challenges"(
  "token",
  "recipient"
);
CREATE INDEX "people_cpf_hash_index" on "people"("cpf_hash");
CREATE INDEX "people_email_hash_index" on "people"("email_hash");
CREATE TABLE IF NOT EXISTS "organization_slug_aliases"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "slug" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("organization_id") references "organizations"("id") on delete cascade
);
CREATE INDEX "organization_slug_aliases_organization_id_index" on "organization_slug_aliases"(
  "organization_id"
);
CREATE UNIQUE INDEX "organization_slug_aliases_slug_unique" on "organization_slug_aliases"(
  "slug"
);
CREATE TABLE IF NOT EXISTS "template_categories"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "key" varchar not null,
  "name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("organization_id") references "organizations"("id") on delete cascade
);
CREATE UNIQUE INDEX "template_categories_organization_id_key_unique" on "template_categories"(
  "organization_id",
  "key"
);
CREATE INDEX "template_categories_organization_id_name_index" on "template_categories"(
  "organization_id",
  "name"
);
CREATE TABLE IF NOT EXISTS "landing_site_visits"(
  "id" integer primary key autoincrement not null,
  "ip_hash" varchar not null,
  "visit_date" date not null,
  "path" varchar not null default '/',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "landing_site_visits_ip_date_path_unique" on "landing_site_visits"(
  "ip_hash",
  "visit_date",
  "path"
);
CREATE INDEX "landing_site_visits_visit_date_path_index" on "landing_site_visits"(
  "visit_date",
  "path"
);
CREATE TABLE IF NOT EXISTS "landing_cta_clicks"(
  "id" integer primary key autoincrement not null,
  "channel" varchar not null,
  "date" date not null,
  "clicks" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "landing_cta_clicks_channel_date_unique" on "landing_cta_clicks"(
  "channel",
  "date"
);
CREATE TABLE IF NOT EXISTS "platform_manual_emails"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "category" varchar not null,
  "recipient_email" varchar not null,
  "recipient_name" varchar,
  "subject" varchar not null,
  "body" text not null,
  "tenant_id" integer,
  "organization_id" integer,
  "lead_id" integer,
  "meta_json" text,
  "sent_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("tenant_id") references "tenants"("id") on delete set null,
  foreign key("organization_id") references "organizations"("id") on delete set null,
  foreign key("lead_id") references "demonstration_requests"("id") on delete set null
);
CREATE INDEX "platform_manual_emails_user_id_created_at_index" on "platform_manual_emails"(
  "user_id",
  "created_at"
);
CREATE INDEX "platform_manual_emails_category_index" on "platform_manual_emails"(
  "category"
);
CREATE TABLE IF NOT EXISTS "release_notes"(
  "id" integer primary key autoincrement not null,
  "version" varchar not null,
  "title" varchar not null,
  "summary" text,
  "items" text not null,
  "released_at" date not null,
  "is_published" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "release_notes_is_published_released_at_index" on "release_notes"(
  "is_published",
  "released_at"
);

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2025_02_22_000001_create_clinics_table',1);
INSERT INTO migrations VALUES(5,'2025_02_22_000002_add_clinic_to_users_table',1);
INSERT INTO migrations VALUES(6,'2025_02_22_000003_create_form_templates_table',1);
INSERT INTO migrations VALUES(7,'2025_02_22_000004_create_form_fields_table',1);
INSERT INTO migrations VALUES(8,'2025_02_22_000005_create_form_submissions_table',1);
INSERT INTO migrations VALUES(9,'2025_02_22_000006_create_submission_values_table',1);
INSERT INTO migrations VALUES(10,'2025_02_22_000007_create_submission_attachments_table',1);
INSERT INTO migrations VALUES(11,'2025_02_22_000008_create_submission_signatures_table',1);
INSERT INTO migrations VALUES(12,'2025_02_22_000009_create_audit_logs_table',1);
INSERT INTO migrations VALUES(13,'2026_02_23_000001_add_theme_to_clinics_table',1);
INSERT INTO migrations VALUES(14,'2026_02_23_000002_add_can_switch_clinic_to_users_table',1);
INSERT INTO migrations VALUES(15,'2026_02_23_000002_add_category_to_form_templates_table',1);
INSERT INTO migrations VALUES(16,'2026_02_28_000001_add_address_to_clinics_table',1);
INSERT INTO migrations VALUES(17,'2026_02_28_000002_create_submission_events_table',1);
INSERT INTO migrations VALUES(18,'2026_02_28_000003_backfill_submission_events_created',1);
INSERT INTO migrations VALUES(19,'2026_02_28_124551_create_notifications_table',1);
INSERT INTO migrations VALUES(20,'2026_02_28_200000_create_clinic_links_table',1);
INSERT INTO migrations VALUES(21,'2026_02_28_210000_add_business_hours_to_clinics_table',1);
INSERT INTO migrations VALUES(22,'2026_02_28_220000_add_public_page_fields_to_clinics_table',1);
INSERT INTO migrations VALUES(23,'2026_03_01_114747_create_personal_access_tokens_table',1);
INSERT INTO migrations VALUES(24,'2026_03_01_120000_create_link_bio_page_views_table',1);
INSERT INTO migrations VALUES(25,'2026_03_01_120001_create_link_bio_link_clicks_table',1);
INSERT INTO migrations VALUES(26,'2026_03_01_140000_create_clinic_webhooks_table',1);
INSERT INTO migrations VALUES(27,'2026_03_01_140001_create_webhook_deliveries_table',1);
INSERT INTO migrations VALUES(28,'2026_03_01_150000_add_billing_fields_to_clinics_table',1);
INSERT INTO migrations VALUES(29,'2026_03_01_150001_create_subscriptions_table',1);
INSERT INTO migrations VALUES(30,'2026_03_01_150002_create_payments_table',1);
INSERT INTO migrations VALUES(31,'2026_03_01_150003_set_trial_for_existing_clinics',1);
INSERT INTO migrations VALUES(32,'2026_03_01_160000_add_whatsapp_notification_fields_to_clinics_table',1);
INSERT INTO migrations VALUES(33,'2026_03_01_170000_add_bank_slip_url_to_payments_table',1);
INSERT INTO migrations VALUES(34,'2026_03_01_180000_create_tenants_table',1);
INSERT INTO migrations VALUES(35,'2026_03_01_180001_add_tenant_id_to_clinics_table',1);
INSERT INTO migrations VALUES(36,'2026_03_01_180002_backfill_tenants_for_existing_clinics',1);
INSERT INTO migrations VALUES(37,'2026_03_06_100000_create_protocol_sequences_table',1);
INSERT INTO migrations VALUES(38,'2026_03_06_100001_add_signature_evidence_to_submission_signatures_table',1);
INSERT INTO migrations VALUES(39,'2026_03_06_100002_migrate_webhook_events_to_submission_names',1);
INSERT INTO migrations VALUES(40,'2026_03_06_100003_add_public_token_expires_at_to_form_templates_table',1);
INSERT INTO migrations VALUES(41,'2026_03_06_110000_rename_clinics_to_organizations',1);
INSERT INTO migrations VALUES(42,'2026_03_06_120000_add_performance_indexes',1);
INSERT INTO migrations VALUES(43,'2026_03_07_100000_add_public_theme_and_cover_color_to_organizations',1);
INSERT INTO migrations VALUES(44,'2026_03_07_120000_create_demonstration_requests_table',1);
INSERT INTO migrations VALUES(45,'2026_03_07_140000_create_platform_settings_table',1);
INSERT INTO migrations VALUES(46,'2026_03_07_140001_create_plans_table',1);
INSERT INTO migrations VALUES(47,'2026_03_15_100000_create_form_template_versions_table',1);
INSERT INTO migrations VALUES(48,'2026_03_15_100001_add_evidence_fields_to_form_submissions_table',1);
INSERT INTO migrations VALUES(49,'2026_03_15_100002_add_evidence_fields_to_submission_signatures_table',1);
INSERT INTO migrations VALUES(50,'2026_03_15_100003_add_meta_json_to_submission_events_table',1);
INSERT INTO migrations VALUES(51,'2026_03_15_100004_create_otp_challenges_table',1);
INSERT INTO migrations VALUES(52,'2026_03_15_100005_create_document_sends_table',1);
INSERT INTO migrations VALUES(53,'2026_03_15_100006_add_signing_security_level_to_organizations_table',1);
INSERT INTO migrations VALUES(54,'2026_03_16_100000_add_cancelled_and_reminded_to_document_sends_table',1);
INSERT INTO migrations VALUES(55,'2026_03_21_120000_add_cover_mode_to_organizations',1);
INSERT INTO migrations VALUES(56,'2026_03_21_180000_add_link_bio_model_to_organizations',1);
INSERT INTO migrations VALUES(57,'2026_03_21_190000_add_link_bio_extra_to_organizations',1);
INSERT INTO migrations VALUES(58,'2026_03_22_100000_create_people_table',1);
INSERT INTO migrations VALUES(59,'2026_03_22_100001_add_person_links',1);
INSERT INTO migrations VALUES(60,'2026_03_28_120000_create_organization_roles_table',1);
INSERT INTO migrations VALUES(61,'2026_03_29_100000_sync_organization_role_permission_defaults',1);
INSERT INTO migrations VALUES(62,'2026_03_29_120000_add_ui_appearance_to_users_table',1);
INSERT INTO migrations VALUES(63,'2026_04_03_120000_add_plan_limits_to_plans_table',1);
INSERT INTO migrations VALUES(64,'2026_04_04_120000_add_evolution_go_to_organizations_table',1);
INSERT INTO migrations VALUES(65,'2026_04_04_120000_rename_plan_names_zionmed_to_gestgo',1);
INSERT INTO migrations VALUES(66,'2026_04_05_120000_rename_executive_plan_gestgo_clinica_to_gestgo_business',1);
INSERT INTO migrations VALUES(67,'2026_04_05_210000_rename_theme_zion_blue_to_gestgo_blue',1);
INSERT INTO migrations VALUES(68,'2026_04_19_120000_keep_only_full_solo_plan',1);
INSERT INTO migrations VALUES(69,'2026_04_20_120000_add_feegow_fields_to_organizations_table',1);
INSERT INTO migrations VALUES(70,'2026_04_20_120000_create_organization_presences_table',1);
INSERT INTO migrations VALUES(71,'2026_04_20_130000_create_feegow_appointments_table',1);
INSERT INTO migrations VALUES(72,'2026_04_21_120000_create_organization_addresses_table',1);
INSERT INTO migrations VALUES(73,'2026_04_21_150000_create_link_bio_cta_clicks_table',1);
INSERT INTO migrations VALUES(74,'2026_04_22_100000_drop_unique_token_from_otp_challenges_table',1);
INSERT INTO migrations VALUES(75,'2026_04_22_100100_add_pii_hash_and_retention_to_people_and_orgs',1);
INSERT INTO migrations VALUES(76,'2026_04_22_120000_add_accent_hex_to_organizations_table',1);
INSERT INTO migrations VALUES(77,'2026_04_22_120000_add_professional_photo_path_to_organizations',1);
INSERT INTO migrations VALUES(78,'2026_04_23_140000_create_organization_slug_aliases_table',1);
INSERT INTO migrations VALUES(79,'2026_04_24_120000_add_niche_to_organizations_table',1);
INSERT INTO migrations VALUES(80,'2026_04_24_230000_add_patient_registration_fields_to_people_table',1);
INSERT INTO migrations VALUES(81,'2026_04_24_235500_refresh_patient_registration_template_fields',1);
INSERT INTO migrations VALUES(82,'2026_04_25_120500_create_template_categories_table',1);
INSERT INTO migrations VALUES(83,'2026_04_25_121500_reclassify_legacy_template_categories',1);
INSERT INTO migrations VALUES(84,'2026_04_25_191200_reset_templates_by_organization_niche',1);
INSERT INTO migrations VALUES(85,'2026_04_26_100000_refresh_estetica_niche_default_templates',1);
INSERT INTO migrations VALUES(86,'2026_04_26_120000_update_estetica_ampersand_labels',1);
INSERT INTO migrations VALUES(87,'2026_04_26_130000_fix_acompanhamento_controle_category_casing',1);
INSERT INTO migrations VALUES(88,'2026_05_03_120000_add_electronic_signature_to_users_table',1);
INSERT INTO migrations VALUES(89,'2026_05_03_140000_add_ui_shell_preset_to_users_table',1);
INSERT INTO migrations VALUES(90,'2026_05_19_120000_create_landing_site_visits_table',1);
INSERT INTO migrations VALUES(91,'2026_05_19_120001_create_landing_cta_clicks_table',1);
INSERT INTO migrations VALUES(92,'2026_05_20_100000_landing_site_visits_unique_per_path',1);
INSERT INTO migrations VALUES(93,'2026_05_20_120000_refresh_veterinaria_niche_default_templates',1);
INSERT INTO migrations VALUES(94,'2026_06_10_120000_seed_business_hub_platform_settings',1);
INSERT INTO migrations VALUES(95,'2026_06_11_120000_seed_asaas_minio_platform_settings',1);
INSERT INTO migrations VALUES(96,'2026_06_11_130000_seed_resend_platform_settings',1);
INSERT INTO migrations VALUES(97,'2026_06_11_140000_create_platform_manual_emails_table',1);
INSERT INTO migrations VALUES(98,'2026_06_14_120000_create_release_notes_table',1);
INSERT INTO migrations VALUES(99,'2026_06_14_150000_add_google_reviews_to_organizations',1);
INSERT INTO migrations VALUES(100,'2026_06_29_120000_add_pix_and_billing_type_to_billing_tables',1);
INSERT INTO migrations VALUES(101,'2026_06_29_120000_ensure_plan_limits_on_plans_table',1);
INSERT INTO migrations VALUES(102,'2026_06_29_180000_add_cpf_hash_index_to_people_table',1);
