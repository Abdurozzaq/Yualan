CREATE TABLE "public"."password_reset_tokens" ( 
  "email" VARCHAR(255) NOT NULL,
  "token" VARCHAR(255) NOT NULL,
  "created_at" TIMESTAMP NULL,
  CONSTRAINT "password_reset_tokens_pkey" PRIMARY KEY ("email")
);
CREATE TABLE "public"."vouchers" ( 
  "id" SERIAL,
  "tenant_id" UUID NOT NULL,
  "code" VARCHAR(255) NOT NULL,
  "name" VARCHAR(255) NOT NULL,
  "type" VARCHAR(255) NOT NULL,
  "value" NUMERIC NOT NULL,
  "max_nominal" NUMERIC NULL,
  "expiry_date" DATE NOT NULL,
  "used" BOOLEAN NOT NULL DEFAULT false ,
  "created_at" TIMESTAMP NULL,
  "updated_at" TIMESTAMP NULL,
  CONSTRAINT "vouchers_pkey" PRIMARY KEY ("id"),
  CONSTRAINT "vouchers_code_unique" UNIQUE ("code")
);
CREATE TABLE "public"."promos" ( 
  "id" SERIAL,
  "tenant_id" UUID NOT NULL,
  "code" VARCHAR(255) NOT NULL,
  "name" VARCHAR(255) NOT NULL,
  "type" VARCHAR(255) NOT NULL,
  "buy_qty" INTEGER NOT NULL,
  "get_qty" INTEGER NOT NULL,
  "product_id" UUID NULL,
  "another_product_id" UUID NULL,
  "expiry_date" DATE NOT NULL,
  "is_active" BOOLEAN NOT NULL DEFAULT true ,
  "created_at" TIMESTAMP NULL,
  "updated_at" TIMESTAMP NULL,
  CONSTRAINT "promos_pkey" PRIMARY KEY ("id"),
  CONSTRAINT "promos_code_unique" UNIQUE ("code")
);
CREATE TABLE "public"."tenants" ( 
  "id" UUID NOT NULL,
  "name" VARCHAR(255) NOT NULL,
  "invitation_code" VARCHAR(255) NOT NULL,
  "slug" VARCHAR(255) NOT NULL,
  "email" VARCHAR(255) NOT NULL,
  "phone" VARCHAR(255) NULL,
  "address" TEXT NULL,
  "city" VARCHAR(255) NULL,
  "state" VARCHAR(255) NULL,
  "zip_code" VARCHAR(255) NULL,
  "country" VARCHAR(255) NULL,
  "business_type" VARCHAR(255) NOT NULL,
  "is_active" BOOLEAN NOT NULL DEFAULT true ,
  "ipaymu_api_key" VARCHAR(255) NULL,
  "ipaymu_secret_key" VARCHAR(255) NULL,
  "ipaymu_mode" VARCHAR(255) NOT NULL DEFAULT 'sandbox'::character varying ,
  "created_at" TIMESTAMP NULL,
  "updated_at" TIMESTAMP NULL,
  "deleted_at" TIMESTAMP NULL,
  "pricing_plan_id" UUID NULL,
  "subscription_ends_at" TIMESTAMP NULL,
  "last_transaction_id" VARCHAR(255) NULL,
  "is_subscribed" BOOLEAN NOT NULL DEFAULT false ,
  "owner_name" VARCHAR(255) NULL,
  "owner_email" VARCHAR(255) NULL,
  "subscription_status" VARCHAR(255) NOT NULL DEFAULT 'trial'::character varying ,
  "midtrans_server_key" VARCHAR(255) NULL,
  "midtrans_client_key" VARCHAR(255) NULL,
  "midtrans_merchant_id" VARCHAR(255) NULL,
  "midtrans_is_production" BOOLEAN NOT NULL DEFAULT false ,
  CONSTRAINT "tenants_pkey" PRIMARY KEY ("id"),
  CONSTRAINT "tenants_email_unique" UNIQUE ("email"),
  CONSTRAINT "tenants_slug_unique" UNIQUE ("slug"),
  CONSTRAINT "tenants_invitation_code_unique" UNIQUE ("invitation_code")
);
CREATE TABLE "public"."sales" ( 
  "id" UUID NOT NULL,
  "tenant_id" UUID NOT NULL,
  "user_id" UUID NOT NULL,
  "customer_id" UUID NULL,
  "invoice_number" VARCHAR(255) NOT NULL,
  "total_amount" NUMERIC NOT NULL,
  "discount_amount" NUMERIC NOT NULL DEFAULT '0'::numeric ,
  "tax_amount" NUMERIC NOT NULL DEFAULT '0'::numeric ,
  "paid_amount" NUMERIC NOT NULL DEFAULT '0'::numeric ,
  "change_amount" NUMERIC NOT NULL DEFAULT '0'::numeric ,
  "payment_method" VARCHAR(255) NOT NULL,
  "status" VARCHAR(255) NOT NULL DEFAULT 'completed'::character varying ,
  "notes" TEXT NULL,
  "created_at" TIMESTAMP NULL,
  "updated_at" TIMESTAMP NULL,
  "deleted_at" TIMESTAMP NULL,
  "subtotal_amount" NUMERIC NOT NULL DEFAULT '0'::numeric ,
  "order_id" VARCHAR(255) NULL,
  "midtrans_transaction_id" VARCHAR(255) NULL,
  "payment_status" VARCHAR(255) NULL,
  "payment_type" VARCHAR(255) NULL,
  "gross_amount" NUMERIC NULL,
  "midtrans_payload" TEXT NULL,
  "promo_code" VARCHAR(255) NULL,
  "voucher_code" VARCHAR(255) NULL,
  CONSTRAINT "sales_pkey" PRIMARY KEY ("id"),
  CONSTRAINT "sales_order_id_unique" UNIQUE ("order_id"),
  CONSTRAINT "sales_invoice_number_unique" UNIQUE ("invoice_number")
);
CREATE TABLE "public"."saas_settings" ( 
  "id" SERIAL,
  "key" VARCHAR(255) NOT NULL,
  "value" TEXT NULL,
  "created_at" TIMESTAMP NULL,
  "updated_at" TIMESTAMP NULL,
  CONSTRAINT "saas_settings_pkey" PRIMARY KEY ("id"),
  CONSTRAINT "saas_settings_key_unique" UNIQUE ("key")
);
CREATE TABLE "public"."saas_invoices" ( 
  "id" SERIAL,
  "tenant_id" UUID NOT NULL,
  "plan_name" VARCHAR(255) NOT NULL,
  "expired_at" DATE NOT NULL,
  "transaction_id" VARCHAR(255) NOT NULL,
  "amount" NUMERIC NOT NULL,
  "created_at" TIMESTAMP NULL,
  "updated_at" TIMESTAMP NULL,
  CONSTRAINT "saas_invoices_pkey" PRIMARY KEY ("id")
);
CREATE TABLE "public"."migrations" ( 
  "id" SERIAL,
  "migration" VARCHAR(255) NOT NULL,
  "batch" INTEGER NOT NULL,
  CONSTRAINT "migrations_pkey" PRIMARY KEY ("id")
);
CREATE TABLE "public"."cache" ( 
  "key" VARCHAR(255) NOT NULL,
  "value" TEXT NOT NULL,
  "expiration" INTEGER NOT NULL,
  CONSTRAINT "cache_pkey" PRIMARY KEY ("key")
);
CREATE TABLE "public"."cache_locks" ( 
  "key" VARCHAR(255) NOT NULL,
  "owner" VARCHAR(255) NOT NULL,
  "expiration" INTEGER NOT NULL,
  CONSTRAINT "cache_locks_pkey" PRIMARY KEY ("key")
);
CREATE TABLE "public"."jobs" ( 
  "id" SERIAL,
  "queue" VARCHAR(255) NOT NULL,
  "payload" TEXT NOT NULL,
  "attempts" SMALLINT NOT NULL,
  "reserved_at" INTEGER NULL,
  "available_at" INTEGER NOT NULL,
  "created_at" INTEGER NOT NULL,
  CONSTRAINT "jobs_pkey" PRIMARY KEY ("id")
);
CREATE TABLE "public"."job_batches" ( 
  "id" VARCHAR(255) NOT NULL,
  "name" VARCHAR(255) NOT NULL,
  "total_jobs" INTEGER NOT NULL,
  "pending_jobs" INTEGER NOT NULL,
  "failed_jobs" INTEGER NOT NULL,
  "failed_job_ids" TEXT NOT NULL,
  "options" TEXT NULL,
  "cancelled_at" INTEGER NULL,
  "created_at" INTEGER NOT NULL,
  "finished_at" INTEGER NULL,
  CONSTRAINT "job_batches_pkey" PRIMARY KEY ("id")
);
CREATE TABLE "public"."failed_jobs" ( 
  "id" SERIAL,
  "uuid" VARCHAR(255) NOT NULL,
  "connection" TEXT NOT NULL,
  "queue" TEXT NOT NULL,
  "payload" TEXT NOT NULL,
  "exception" TEXT NOT NULL,
  "failed_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  CONSTRAINT "failed_jobs_pkey" PRIMARY KEY ("id"),
  CONSTRAINT "failed_jobs_uuid_unique" UNIQUE ("uuid")
);
CREATE TABLE "public"."users" ( 
  "id" UUID NOT NULL,
  "tenant_id" UUID NULL,
  "name" VARCHAR(255) NOT NULL,
  "email" VARCHAR(255) NOT NULL,
  "email_verified_at" TIMESTAMP NULL,
  "password" VARCHAR(255) NOT NULL,
  "role" VARCHAR(255) NOT NULL DEFAULT 'cashier'::character varying ,
  "remember_token" VARCHAR(100) NULL,
  "created_at" TIMESTAMP NULL,
  "updated_at" TIMESTAMP NULL,
  "deleted_at" TIMESTAMP NULL,
  CONSTRAINT "users_pkey" PRIMARY KEY ("id"),
  CONSTRAINT "users_email_unique" UNIQUE ("email")
);
CREATE TABLE "public"."categories" ( 
  "id" UUID NOT NULL,
  "tenant_id" UUID NOT NULL,
  "name" VARCHAR(255) NOT NULL,
  "description" TEXT NULL,
  "created_at" TIMESTAMP NULL,
  "updated_at" TIMESTAMP NULL,
  "deleted_at" TIMESTAMP NULL,
  CONSTRAINT "categories_pkey" PRIMARY KEY ("id"),
  CONSTRAINT "categories_tenant_id_name_unique" UNIQUE ("tenant_id", "name")
);
CREATE TABLE "public"."customers" ( 
  "id" UUID NOT NULL,
  "tenant_id" UUID NOT NULL,
  "name" VARCHAR(255) NOT NULL,
  "email" VARCHAR(255) NULL,
  "phone" VARCHAR(255) NULL,
  "address" TEXT NULL,
  "created_at" TIMESTAMP NULL,
  "updated_at" TIMESTAMP NULL,
  "deleted_at" TIMESTAMP NULL,
  CONSTRAINT "customers_pkey" PRIMARY KEY ("id")
);
CREATE TABLE "public"."tables" ( 
  "id" UUID NOT NULL,
  "tenant_id" UUID NOT NULL,
  "name" VARCHAR(255) NOT NULL,
  "capacity" INTEGER NULL,
  "status" VARCHAR(255) NOT NULL DEFAULT 'available'::character varying ,
  "created_at" TIMESTAMP NULL,
  "updated_at" TIMESTAMP NULL,
  "deleted_at" TIMESTAMP NULL,
  CONSTRAINT "tables_pkey" PRIMARY KEY ("id"),
  CONSTRAINT "tables_tenant_id_name_unique" UNIQUE ("tenant_id", "name")
);
CREATE TABLE "public"."suppliers" ( 
  "id" UUID NOT NULL,
  "tenant_id" UUID NOT NULL,
  "name" VARCHAR(255) NOT NULL,
  "contact_person" VARCHAR(255) NULL,
  "phone" VARCHAR(255) NULL,
  "email" VARCHAR(255) NULL,
  "address" TEXT NULL,
  "notes" TEXT NULL,
  "created_at" TIMESTAMP NULL,
  "updated_at" TIMESTAMP NULL,
  "deleted_at" TIMESTAMP NULL,
  CONSTRAINT "suppliers_pkey" PRIMARY KEY ("id")
);
CREATE TABLE "public"."payments" ( 
  "id" UUID NOT NULL,
  "tenant_id" UUID NOT NULL,
  "sale_id" UUID NULL,
  "payment_method" VARCHAR(255) NOT NULL,
  "amount" NUMERIC NOT NULL,
  "currency" VARCHAR(255) NOT NULL DEFAULT 'IDR'::character varying ,
  "status" VARCHAR(255) NOT NULL DEFAULT 'pending'::character varying ,
  "transaction_id" VARCHAR(255) NULL,
  "gateway_response" JSONB NULL,
  "notes" TEXT NULL,
  "created_at" TIMESTAMP NULL,
  "updated_at" TIMESTAMP NULL,
  CONSTRAINT "payments_pkey" PRIMARY KEY ("id"),
  CONSTRAINT "payments_transaction_id_unique" UNIQUE ("transaction_id")
);
CREATE TABLE "public"."inventories" ( 
  "id" UUID NOT NULL,
  "tenant_id" UUID NOT NULL,
  "product_id" UUID NOT NULL,
  "quantity_change" INTEGER NOT NULL,
  "type" VARCHAR(255) NOT NULL,
  "reason" TEXT NULL,
  "source_id" UUID NULL,
  "source_type" VARCHAR(255) NULL,
  "created_at" TIMESTAMP NULL,
  "updated_at" TIMESTAMP NULL,
  "cost_per_unit" NUMERIC NOT NULL DEFAULT '0'::numeric ,
  "deleted_at" TIMESTAMP NULL,
  CONSTRAINT "inventories_pkey" PRIMARY KEY ("id")
);
CREATE TABLE "public"."products" ( 
  "id" UUID NOT NULL,
  "tenant_id" UUID NOT NULL,
  "category_id" UUID NULL,
  "name" VARCHAR(255) NOT NULL,
  "sku" VARCHAR(255) NULL,
  "description" TEXT NULL,
  "price" NUMERIC NOT NULL,
  "stock" INTEGER NOT NULL DEFAULT 0 ,
  "unit" VARCHAR(255) NULL,
  "image" VARCHAR(255) NULL,
  "is_food_item" BOOLEAN NOT NULL DEFAULT false ,
  "ingredients" TEXT NULL,
  "created_at" TIMESTAMP NULL,
  "updated_at" TIMESTAMP NULL,
  "deleted_at" TIMESTAMP NULL,
  "cost_price" NUMERIC NOT NULL DEFAULT '0'::numeric ,
  "min_stock_level" INTEGER NULL,
  CONSTRAINT "products_pkey" PRIMARY KEY ("id"),
  CONSTRAINT "products_sku_unique" UNIQUE ("sku")
);
CREATE TABLE "public"."sale_items" ( 
  "id" UUID NOT NULL,
  "sale_id" UUID NOT NULL,
  "product_id" UUID NOT NULL,
  "quantity" INTEGER NOT NULL,
  "price" NUMERIC NOT NULL,
  "subtotal" NUMERIC NOT NULL,
  "created_at" TIMESTAMP NULL,
  "updated_at" TIMESTAMP NULL,
  "cost_price_at_sale" NUMERIC NOT NULL DEFAULT '0'::numeric ,
  CONSTRAINT "sale_items_pkey" PRIMARY KEY ("id")
);
CREATE TABLE "public"."pricing_plans" ( 
  "id" UUID NOT NULL,
  "plan_name" VARCHAR(255) NOT NULL,
  "plan_description" VARCHAR(255) NULL,
  "period_type" VARCHAR(255) NOT NULL,
  "price" NUMERIC NOT NULL,
  "discount_percentage" NUMERIC NOT NULL DEFAULT '0'::numeric ,
  "created_at" TIMESTAMP NULL,
  "updated_at" TIMESTAMP NULL,
  CONSTRAINT "pricing_plans_pkey" PRIMARY KEY ("id")
);
ALTER TABLE "public"."password_reset_tokens" DISABLE TRIGGER ALL;
ALTER TABLE "public"."vouchers" DISABLE TRIGGER ALL;
ALTER TABLE "public"."promos" DISABLE TRIGGER ALL;
ALTER TABLE "public"."tenants" DISABLE TRIGGER ALL;
ALTER TABLE "public"."sales" DISABLE TRIGGER ALL;
ALTER TABLE "public"."saas_settings" DISABLE TRIGGER ALL;
ALTER TABLE "public"."saas_invoices" DISABLE TRIGGER ALL;
ALTER TABLE "public"."migrations" DISABLE TRIGGER ALL;
ALTER TABLE "public"."cache" DISABLE TRIGGER ALL;
ALTER TABLE "public"."cache_locks" DISABLE TRIGGER ALL;
ALTER TABLE "public"."jobs" DISABLE TRIGGER ALL;
ALTER TABLE "public"."job_batches" DISABLE TRIGGER ALL;
ALTER TABLE "public"."failed_jobs" DISABLE TRIGGER ALL;
ALTER TABLE "public"."users" DISABLE TRIGGER ALL;
ALTER TABLE "public"."categories" DISABLE TRIGGER ALL;
ALTER TABLE "public"."customers" DISABLE TRIGGER ALL;
ALTER TABLE "public"."tables" DISABLE TRIGGER ALL;
ALTER TABLE "public"."suppliers" DISABLE TRIGGER ALL;
ALTER TABLE "public"."payments" DISABLE TRIGGER ALL;
ALTER TABLE "public"."inventories" DISABLE TRIGGER ALL;
ALTER TABLE "public"."products" DISABLE TRIGGER ALL;
ALTER TABLE "public"."sale_items" DISABLE TRIGGER ALL;
ALTER TABLE "public"."pricing_plans" DISABLE TRIGGER ALL;
INSERT INTO "public"."password_reset_tokens" ("email", "token", "created_at") VALUES ('abdurozzaq00@gmail.com', '$2y$12$vGQAsQegt9nfsKvz71sfdOKrohIUQcWa4J4vjHEg01PkyzRxytjfm', '2025-08-14 05:23:20');
INSERT INTO "public"."tenants" ("id", "name", "invitation_code", "slug", "email", "phone", "address", "city", "state", "zip_code", "country", "business_type", "is_active", "ipaymu_api_key", "ipaymu_secret_key", "ipaymu_mode", "created_at", "updated_at", "deleted_at", "pricing_plan_id", "subscription_ends_at", "last_transaction_id", "is_subscribed", "owner_name", "owner_email", "subscription_status", "midtrans_server_key", "midtrans_client_key", "midtrans_merchant_id", "midtrans_is_production") VALUES ('5b5b91ec-db50-4c06-aa88-5f2bfcd271b7', 'Notena Project', 'aNcCdP3myq', 'notena-project-gsbkz', 'fajarnurprasetyo.2@gmail.com', '085195206660', 'Jl. Peltu Soebowo Soepangat, RT.07 RW.02, Desa Klero', 'Kab. Semarang', 'Jawa Tengah', NULL, NULL, 'Toko', true, NULL, NULL, 'sandbox', '2025-08-09 10:09:57', '2025-08-09 10:09:57', NULL, NULL, '2025-08-16 10:09:57', NULL, true, NULL, NULL, 'trial', NULL, NULL, NULL, false);
INSERT INTO "public"."tenants" ("id", "name", "invitation_code", "slug", "email", "phone", "address", "city", "state", "zip_code", "country", "business_type", "is_active", "ipaymu_api_key", "ipaymu_secret_key", "ipaymu_mode", "created_at", "updated_at", "deleted_at", "pricing_plan_id", "subscription_ends_at", "last_transaction_id", "is_subscribed", "owner_name", "owner_email", "subscription_status", "midtrans_server_key", "midtrans_client_key", "midtrans_merchant_id", "midtrans_is_production") VALUES ('57cd59e8-b4f0-4032-8e09-7084a7057e53', 'halim', 'g7WLcNhH6k', 'halim-1sphi', 'anhalimm@gmail.com', '85647847677', 'ahmad', 'yogyakarta', 'diy', NULL, NULL, 'Toko', true, NULL, NULL, 'sandbox', '2025-08-09 14:37:35', '2025-08-09 14:38:27', NULL, NULL, '2025-08-16 14:37:35', NULL, true, NULL, NULL, 'trial', NULL, NULL, NULL, false);
INSERT INTO "public"."tenants" ("id", "name", "invitation_code", "slug", "email", "phone", "address", "city", "state", "zip_code", "country", "business_type", "is_active", "ipaymu_api_key", "ipaymu_secret_key", "ipaymu_mode", "created_at", "updated_at", "deleted_at", "pricing_plan_id", "subscription_ends_at", "last_transaction_id", "is_subscribed", "owner_name", "owner_email", "subscription_status", "midtrans_server_key", "midtrans_client_key", "midtrans_merchant_id", "midtrans_is_production") VALUES ('43120495-d74c-4d15-a502-e56a57fd62b3', 'demotes', '634Y0CbTtf', 'demotes-9o5sp', 'adeulala5@gmail.com', '081973613029', 'jalan kebon kacang 3 no.20, tanah abang, jakpus, dki Jakarta, 10240', 'JAKARTA', 'DKI JAKARTA', NULL, NULL, 'demotes', true, NULL, NULL, 'sandbox', '2025-08-11 02:20:26', '2025-08-11 02:20:26', NULL, NULL, '2025-08-18 02:20:26', NULL, true, NULL, NULL, 'trial', NULL, NULL, NULL, false);
INSERT INTO "public"."tenants" ("id", "name", "invitation_code", "slug", "email", "phone", "address", "city", "state", "zip_code", "country", "business_type", "is_active", "ipaymu_api_key", "ipaymu_secret_key", "ipaymu_mode", "created_at", "updated_at", "deleted_at", "pricing_plan_id", "subscription_ends_at", "last_transaction_id", "is_subscribed", "owner_name", "owner_email", "subscription_status", "midtrans_server_key", "midtrans_client_key", "midtrans_merchant_id", "midtrans_is_production") VALUES ('f48dc991-f323-4749-8a28-ee45af816024', 'g', 'yokVuXGVzL', 'g-vzqay', 'vsheh@gg.id', '66374774', NULL, NULL, NULL, NULL, NULL, 'b', true, NULL, NULL, 'sandbox', '2025-08-19 09:48:54', '2025-08-19 09:49:27', NULL, NULL, '2025-08-26 09:48:54', NULL, true, NULL, NULL, 'trial', NULL, NULL, NULL, false);
INSERT INTO "public"."tenants" ("id", "name", "invitation_code", "slug", "email", "phone", "address", "city", "state", "zip_code", "country", "business_type", "is_active", "ipaymu_api_key", "ipaymu_secret_key", "ipaymu_mode", "created_at", "updated_at", "deleted_at", "pricing_plan_id", "subscription_ends_at", "last_transaction_id", "is_subscribed", "owner_name", "owner_email", "subscription_status", "midtrans_server_key", "midtrans_client_key", "midtrans_merchant_id", "midtrans_is_production") VALUES ('8a3b1756-349b-41cf-bb5e-bdcaba3f36df', 'RIANG ATK', '03CAcM5tIV', 'riang-atk-xug48', 'riang.pgk@gmail.com', '085896909846', 'Jl. Fatmawati', 'Pangkalpinang', 'Kepulauan Bangka Belitung', NULL, NULL, 'Toko', true, NULL, NULL, 'sandbox', '2025-08-20 00:22:52', '2025-08-20 00:22:52', NULL, NULL, '2025-08-27 00:22:52', NULL, true, NULL, NULL, 'trial', NULL, NULL, NULL, false);
INSERT INTO "public"."tenants" ("id", "name", "invitation_code", "slug", "email", "phone", "address", "city", "state", "zip_code", "country", "business_type", "is_active", "ipaymu_api_key", "ipaymu_secret_key", "ipaymu_mode", "created_at", "updated_at", "deleted_at", "pricing_plan_id", "subscription_ends_at", "last_transaction_id", "is_subscribed", "owner_name", "owner_email", "subscription_status", "midtrans_server_key", "midtrans_client_key", "midtrans_merchant_id", "midtrans_is_production") VALUES ('cb6c9def-98f6-4a0d-a80e-1ebdedf9627f', 'mpwoid', 'T2oqfdLsXP', 'mpwoid-iknqx', 'mpwoid@gmail.com', '085600000000', 'ok', NULL, NULL, NULL, NULL, 'Restoran', true, NULL, NULL, 'sandbox', '2025-08-20 13:05:09', '2025-08-20 13:05:09', NULL, NULL, '2025-08-27 13:05:09', NULL, true, NULL, NULL, 'trial', NULL, NULL, NULL, false);
INSERT INTO "public"."tenants" ("id", "name", "invitation_code", "slug", "email", "phone", "address", "city", "state", "zip_code", "country", "business_type", "is_active", "ipaymu_api_key", "ipaymu_secret_key", "ipaymu_mode", "created_at", "updated_at", "deleted_at", "pricing_plan_id", "subscription_ends_at", "last_transaction_id", "is_subscribed", "owner_name", "owner_email", "subscription_status", "midtrans_server_key", "midtrans_client_key", "midtrans_merchant_id", "midtrans_is_production") VALUES ('8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'Rozzaq Store', 'hgukhFw4eU', 'rozzaq-store-k4yiu', 'abdurozzaq00@gmail.com', '089603363136', 'Kp. Sentul No. 57 Rt.005 Rw.004', 'Kab. Tangerang', 'Banten', NULL, NULL, 'Toko', true, '0000005113787775', 'SANDBOX0F54ABB1-ABEC-41A1-BEEF-BAF6B5777620', 'sandbox', '2025-08-08 20:51:41', '2025-08-24 11:37:14', NULL, '2b3675ce-5467-4825-8ad3-20884121c75f', '2025-09-14 04:42:49', '24381745', true, NULL, NULL, 'trial', NULL, NULL, NULL, false);
INSERT INTO "public"."tenants" ("id", "name", "invitation_code", "slug", "email", "phone", "address", "city", "state", "zip_code", "country", "business_type", "is_active", "ipaymu_api_key", "ipaymu_secret_key", "ipaymu_mode", "created_at", "updated_at", "deleted_at", "pricing_plan_id", "subscription_ends_at", "last_transaction_id", "is_subscribed", "owner_name", "owner_email", "subscription_status", "midtrans_server_key", "midtrans_client_key", "midtrans_merchant_id", "midtrans_is_production") VALUES ('178284e6-b464-42df-adff-e8dae85d4296', 'Tatang Market', '5RCz5ORL9I', 'tatang-market-m93wi', 'tatang@gmail.com', '089603363136', 'Kp. Sentul No. 57 Rt.005 Rw.004', 'Kab. Tangerang', 'Banten', NULL, NULL, 'Toko', true, NULL, NULL, 'sandbox', '2025-08-24 11:38:33', '2025-08-24 11:38:33', NULL, NULL, '2025-08-31 11:38:33', NULL, false, NULL, NULL, 'trial', NULL, NULL, NULL, false);
INSERT INTO "public"."tenants" ("id", "name", "invitation_code", "slug", "email", "phone", "address", "city", "state", "zip_code", "country", "business_type", "is_active", "ipaymu_api_key", "ipaymu_secret_key", "ipaymu_mode", "created_at", "updated_at", "deleted_at", "pricing_plan_id", "subscription_ends_at", "last_transaction_id", "is_subscribed", "owner_name", "owner_email", "subscription_status", "midtrans_server_key", "midtrans_client_key", "midtrans_merchant_id", "midtrans_is_production") VALUES ('90139248-5a59-37af-bd9f-5570cd380f2f', 'Dooley, Berge and Hayes', 'CTNBLHEQ', 'balistreri-graham', 'jarrod.heathcote@example.org', '1-361-834-0955', '68939 Turner Plains Apt. 233
D''Amorebury, ME 21472-6559', 'Lizethside', 'Nevada', '31200-8505', 'Finland', 'autem', true, '8fc1c12c-726a-3272-85ea-578eb80d6d87', '1818fc5745f9d3f054f47c9aa733c13e66ccdebb40bcf563b7d59a8d956bb24b', 'sandbox', '2025-08-28 06:56:11', '2025-08-28 06:56:11', NULL, '90a3b694-955d-3a02-b3c9-35df001837bc', '2026-04-01 21:13:32', 'd2aefca8-15ad-3a64-b74a-b9195a12fc95', false, 'Prof. Krystina Flatley Sr.', 'jarvis70@example.com', 'active', NULL, NULL, NULL, false);
INSERT INTO "public"."tenants" ("id", "name", "invitation_code", "slug", "email", "phone", "address", "city", "state", "zip_code", "country", "business_type", "is_active", "ipaymu_api_key", "ipaymu_secret_key", "ipaymu_mode", "created_at", "updated_at", "deleted_at", "pricing_plan_id", "subscription_ends_at", "last_transaction_id", "is_subscribed", "owner_name", "owner_email", "subscription_status", "midtrans_server_key", "midtrans_client_key", "midtrans_merchant_id", "midtrans_is_production") VALUES ('4e691cc0-1156-49c7-aa70-450c9f9c1760', 'Personal', '5JPUcO06Z0', 'personal-pp9hg', 'muhaimin@gmail.com', '08563533199', 'Mawar', 'BLITAR', 'Jawa Timur', NULL, NULL, 'Toko', true, NULL, NULL, 'sandbox', '2025-08-30 16:52:05', '2025-08-30 16:52:05', NULL, NULL, '2025-09-06 16:52:05', NULL, true, NULL, NULL, 'trial', NULL, NULL, NULL, false);
INSERT INTO "public"."sales" ("id", "tenant_id", "user_id", "customer_id", "invoice_number", "total_amount", "discount_amount", "tax_amount", "paid_amount", "change_amount", "payment_method", "status", "notes", "created_at", "updated_at", "deleted_at", "subtotal_amount", "order_id", "midtrans_transaction_id", "payment_status", "payment_type", "gross_amount", "midtrans_payload", "promo_code", "voucher_code") VALUES ('97d80cf2-0c4a-4eb7-ae7c-89efaafefe92', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '07e0eb2d-b88f-4f0a-81ed-5f5770e033ce', NULL, 'INV-20250809062330-BJV5', '500000.00', '0.00', '0.00', '500000.00', '0.00', 'cash', 'completed', NULL, '2025-08-09 06:23:30', '2025-08-09 06:23:30', NULL, '500000.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO "public"."sales" ("id", "tenant_id", "user_id", "customer_id", "invoice_number", "total_amount", "discount_amount", "tax_amount", "paid_amount", "change_amount", "payment_method", "status", "notes", "created_at", "updated_at", "deleted_at", "subtotal_amount", "order_id", "midtrans_transaction_id", "payment_status", "payment_type", "gross_amount", "midtrans_payload", "promo_code", "voucher_code") VALUES ('dd372ee8-3d4f-49d8-b661-aef7c1c5f504', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '07e0eb2d-b88f-4f0a-81ed-5f5770e033ce', NULL, 'INV-20250809063441-TTcj', '500000.00', '0.00', '0.00', '500000.00', '0.00', 'ipaymu', 'pending', NULL, '2025-08-09 06:34:41', '2025-08-09 06:34:41', NULL, '500000.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO "public"."sales" ("id", "tenant_id", "user_id", "customer_id", "invoice_number", "total_amount", "discount_amount", "tax_amount", "paid_amount", "change_amount", "payment_method", "status", "notes", "created_at", "updated_at", "deleted_at", "subtotal_amount", "order_id", "midtrans_transaction_id", "payment_status", "payment_type", "gross_amount", "midtrans_payload", "promo_code", "voucher_code") VALUES ('b24bf096-db7d-442b-82a3-0798ec6e172b', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '07e0eb2d-b88f-4f0a-81ed-5f5770e033ce', NULL, 'INV-20250809065354-aYXc', '500000.00', '0.00', '0.00', '500000.00', '0.00', 'ipaymu', 'pending', 'Menunggu pembayaran via iPaymu.', '2025-08-09 06:53:54', '2025-08-09 06:53:56', NULL, '500000.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO "public"."sales" ("id", "tenant_id", "user_id", "customer_id", "invoice_number", "total_amount", "discount_amount", "tax_amount", "paid_amount", "change_amount", "payment_method", "status", "notes", "created_at", "updated_at", "deleted_at", "subtotal_amount", "order_id", "midtrans_transaction_id", "payment_status", "payment_type", "gross_amount", "midtrans_payload", "promo_code", "voucher_code") VALUES ('7781eabb-d889-483d-b914-ca76b10b89ad', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '07e0eb2d-b88f-4f0a-81ed-5f5770e033ce', NULL, 'INV-20250809065457-zSxz', '1000000.00', '0.00', '0.00', '1000000.00', '0.00', 'ipaymu', 'completed', 'Pembayaran berhasil via iPaymu (TRX ID: 174544)', '2025-08-09 06:54:57', '2025-08-09 06:55:24', NULL, '1000000.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO "public"."sales" ("id", "tenant_id", "user_id", "customer_id", "invoice_number", "total_amount", "discount_amount", "tax_amount", "paid_amount", "change_amount", "payment_method", "status", "notes", "created_at", "updated_at", "deleted_at", "subtotal_amount", "order_id", "midtrans_transaction_id", "payment_status", "payment_type", "gross_amount", "midtrans_payload", "promo_code", "voucher_code") VALUES ('82ad3aae-f3ea-405f-ab73-75488c11a8ea', '57cd59e8-b4f0-4032-8e09-7084a7057e53', 'af5c3f65-5aa5-45fe-a126-78fda4938e1d', NULL, 'INV-20250809144334-adAS', '100000.00', '0.00', '0.00', '100000.00', '0.00', 'cash', 'completed', NULL, '2025-08-09 14:43:34', '2025-08-09 14:43:34', NULL, '100000.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO "public"."sales" ("id", "tenant_id", "user_id", "customer_id", "invoice_number", "total_amount", "discount_amount", "tax_amount", "paid_amount", "change_amount", "payment_method", "status", "notes", "created_at", "updated_at", "deleted_at", "subtotal_amount", "order_id", "midtrans_transaction_id", "payment_status", "payment_type", "gross_amount", "midtrans_payload", "promo_code", "voucher_code") VALUES ('d434dc3e-c3ff-4931-8ded-ef23459459eb', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '07e0eb2d-b88f-4f0a-81ed-5f5770e033ce', NULL, 'INV-20250811134832-Sund', '500000.00', '0.00', '0.00', '500000.00', '0.00', 'ipaymu', 'pending', 'Menunggu pembayaran via iPaymu.', '2025-08-11 13:48:32', '2025-08-11 13:48:34', NULL, '500000.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO "public"."sales" ("id", "tenant_id", "user_id", "customer_id", "invoice_number", "total_amount", "discount_amount", "tax_amount", "paid_amount", "change_amount", "payment_method", "status", "notes", "created_at", "updated_at", "deleted_at", "subtotal_amount", "order_id", "midtrans_transaction_id", "payment_status", "payment_type", "gross_amount", "midtrans_payload", "promo_code", "voucher_code") VALUES ('0445050a-691d-4b89-bd76-35d5428b0505', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '07e0eb2d-b88f-4f0a-81ed-5f5770e033ce', NULL, 'INV-20250824112507-CxR9', '170000.00', '0.00', '0.00', '170000.00', '0.00', 'ipaymu', 'pending', 'Menunggu pembayaran via iPaymu.', '2025-08-24 11:25:07', '2025-08-24 11:25:09', NULL, '170000.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO "public"."sales" ("id", "tenant_id", "user_id", "customer_id", "invoice_number", "total_amount", "discount_amount", "tax_amount", "paid_amount", "change_amount", "payment_method", "status", "notes", "created_at", "updated_at", "deleted_at", "subtotal_amount", "order_id", "midtrans_transaction_id", "payment_status", "payment_type", "gross_amount", "midtrans_payload", "promo_code", "voucher_code") VALUES ('c1787a56-d6f7-4884-989d-ae54effc60d3', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '07e0eb2d-b88f-4f0a-81ed-5f5770e033ce', NULL, 'INV-20250824112512-B2Qp', '170000.00', '0.00', '0.00', '170000.00', '0.00', 'ipaymu', 'pending', 'Menunggu pembayaran via iPaymu.', '2025-08-24 11:25:12', '2025-08-24 11:25:14', NULL, '170000.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO "public"."sales" ("id", "tenant_id", "user_id", "customer_id", "invoice_number", "total_amount", "discount_amount", "tax_amount", "paid_amount", "change_amount", "payment_method", "status", "notes", "created_at", "updated_at", "deleted_at", "subtotal_amount", "order_id", "midtrans_transaction_id", "payment_status", "payment_type", "gross_amount", "midtrans_payload", "promo_code", "voucher_code") VALUES ('3db056a5-cc7b-4a2e-8d54-12ea1ab372e0', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '07e0eb2d-b88f-4f0a-81ed-5f5770e033ce', NULL, 'INV-20250824112856-9QUw', '45000.00', '0.00', '0.00', '45000.00', '0.00', 'ipaymu', 'pending', 'Menunggu pembayaran via iPaymu.', '2025-08-24 11:28:56', '2025-08-24 11:28:57', NULL, '45000.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO "public"."sales" ("id", "tenant_id", "user_id", "customer_id", "invoice_number", "total_amount", "discount_amount", "tax_amount", "paid_amount", "change_amount", "payment_method", "status", "notes", "created_at", "updated_at", "deleted_at", "subtotal_amount", "order_id", "midtrans_transaction_id", "payment_status", "payment_type", "gross_amount", "midtrans_payload", "promo_code", "voucher_code") VALUES ('8435142f-3a2c-4b41-b6f1-fc07a51a52ca', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '07e0eb2d-b88f-4f0a-81ed-5f5770e033ce', NULL, 'INV-20250824113720-lq8x', '15000.00', '0.00', '0.00', '15000.00', '0.00', 'ipaymu', 'pending', 'Menunggu pembayaran via iPaymu.', '2025-08-24 11:37:20', '2025-08-24 11:37:21', NULL, '15000.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO "public"."sales" ("id", "tenant_id", "user_id", "customer_id", "invoice_number", "total_amount", "discount_amount", "tax_amount", "paid_amount", "change_amount", "payment_method", "status", "notes", "created_at", "updated_at", "deleted_at", "subtotal_amount", "order_id", "midtrans_transaction_id", "payment_status", "payment_type", "gross_amount", "midtrans_payload", "promo_code", "voucher_code") VALUES ('d5fb7dcb-9635-4b58-9f99-42a049b4684e', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '07e0eb2d-b88f-4f0a-81ed-5f5770e033ce', NULL, 'INV-20250824114901-rKwv', '15000.00', '0.00', '0.00', '15000.00', '0.00', 'ipaymu', 'pending', 'Menunggu pembayaran via iPaymu.', '2025-08-24 11:49:01', '2025-08-24 11:49:03', NULL, '15000.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO "public"."sales" ("id", "tenant_id", "user_id", "customer_id", "invoice_number", "total_amount", "discount_amount", "tax_amount", "paid_amount", "change_amount", "payment_method", "status", "notes", "created_at", "updated_at", "deleted_at", "subtotal_amount", "order_id", "midtrans_transaction_id", "payment_status", "payment_type", "gross_amount", "midtrans_payload", "promo_code", "voucher_code") VALUES ('4335850a-8d3d-46f1-8c75-162e3549c115', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '07e0eb2d-b88f-4f0a-81ed-5f5770e033ce', NULL, 'INV-20250824115538-32MR', '15000.00', '0.00', '0.00', '15000.00', '0.00', 'ipaymu', 'pending', 'Menunggu pembayaran via iPaymu.', '2025-08-24 11:55:38', '2025-08-24 11:55:39', NULL, '15000.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO "public"."saas_settings" ("id", "key", "value", "created_at", "updated_at") VALUES ('1', 'ipaymu_va', '1179009603363136', '2025-08-08 19:55:12', '2025-08-08 19:55:12');
INSERT INTO "public"."saas_settings" ("id", "key", "value", "created_at", "updated_at") VALUES ('2', 'ipaymu_api_key', 'A59A0FCA-E907-4CAD-B26B-AFEEF0161369', '2025-08-08 19:55:12', '2025-08-08 19:55:12');
INSERT INTO "public"."saas_settings" ("id", "key", "value", "created_at", "updated_at") VALUES ('3', 'trial_days', '7', '2025-08-08 19:55:12', '2025-08-08 19:55:12');
INSERT INTO "public"."saas_invoices" ("id", "tenant_id", "plan_name", "expired_at", "transaction_id", "amount", "created_at", "updated_at") VALUES ('1', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '1 Bulan', '2025-09-14', '24381553', '15374.00', '2025-08-14 04:38:37', '2025-08-14 04:38:37');
INSERT INTO "public"."saas_invoices" ("id", "tenant_id", "plan_name", "expired_at", "transaction_id", "amount", "created_at", "updated_at") VALUES ('2', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '1 Bulan', '2025-09-14', '24381745', '15374.00', '2025-08-14 04:42:49', '2025-08-14 04:42:49');
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (1, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (2, '0001_01_01_000002_create_jobs_table', 1);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (3, '2025_07_11_090405_create_tenants_table', 1);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (4, '2025_07_11_090406_create_users_table', 1);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (5, '2025_07_11_090547_create_categories_table', 1);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (6, '2025_07_11_090605_create_products_table', 1);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (7, '2025_07_11_090622_create_customers_table', 1);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (8, '2025_07_11_090645_create_sales_table', 1);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (9, '2025_07_11_090658_create_sale_items_table', 1);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (10, '2025_07_11_090710_create_payments_table', 1);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (11, '2025_07_11_090728_create_inventories_table', 1);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (12, '2025_07_11_090803_create_table_configurations', 1);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (13, '2025_07_15_082324_add_subtotal_amount_to_sales_table', 1);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (14, '2025_07_23_050109_add_cost_price_and_min_stock_level_to_products_table', 1);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (15, '2025_07_23_050118_add_cost_price_at_sale_to_sale_items_table', 1);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (16, '2025_07_23_050123_add_cost_per_unit_to_inventories_table', 1);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (17, '2025_07_23_050129_create_suppliers_table', 1);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (18, '2025_07_23_071153_add_soft_deletes_to_inventories_table', 1);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (19, '2025_08_08_152614_create_pricing_plans_and_update_tenants_table', 1);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (20, '2025_08_08_154900_create_saas_settings_table', 1);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (21, '2025_08_09_000001_create_saas_invoices_table', 1);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (22, '2025_08_14_052153_create_password_reset_tokens_table', 2);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (23, '2025_08_16_064015_update_tenants_table', 3);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (24, '2025_08_17_000001_add_midtrans_credentials_to_tenants_table', 4);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (25, '2025_08_17_000002_add_midtrans_is_production_to_tenants_table', 4);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (26, '2025_08_17_100000_add_midtrans_fields_to_sales_table', 4);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (27, '2025_08_27_000001_add_promo_code_to_sales_table', 5);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (28, '2025_08_27_134758_create_vouchers_table', 5);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (29, '2025_08_27_134803_create_promos_table', 5);
INSERT INTO "public"."migrations" ("id", "migration", "batch") VALUES (30, '2025_08_27_150000_add_voucher_code_to_sales_table', 5);
INSERT INTO "public"."cache" ("key", "value", "expiration") VALUES ('yualan_pos_hosted_cache_sari@gmail.com|103.136.59.68:timer', 'i:1754700781;', 1754700781);
INSERT INTO "public"."cache" ("key", "value", "expiration") VALUES ('yualan_pos_hosted_cache_sari@gmail.com|103.136.59.68', 'i:1;', 1754700781);
INSERT INTO "public"."cache" ("key", "value", "expiration") VALUES ('yualan_pos_hosted_cache_stoordigital@gmail.com|114.79.54.41:timer', 'i:1754732562;', 1754732562);
INSERT INTO "public"."cache" ("key", "value", "expiration") VALUES ('yualan_pos_hosted_cache_stoordigital@gmail.com|114.79.54.41', 'i:1;', 1754732562);
INSERT INTO "public"."cache" ("key", "value", "expiration") VALUES ('yualan_pos_hosted_cache_danysetiyawan50@gmail.com|103.162.221.132:timer', 'i:1754741630;', 1754741630);
INSERT INTO "public"."cache" ("key", "value", "expiration") VALUES ('yualan_pos_hosted_cache_danysetiyawan50@gmail.com|103.162.221.132', 'i:1;', 1754741630);
INSERT INTO "public"."cache" ("key", "value", "expiration") VALUES ('yualan_pos_hosted_cache_adeulala5@gmail.com|149.108.104.194:timer', 'i:1754878811;', 1754878811);
INSERT INTO "public"."cache" ("key", "value", "expiration") VALUES ('yualan_pos_hosted_cache_adeulala5@gmail.com|149.108.104.194', 'i:1;', 1754878811);
INSERT INTO "public"."cache" ("key", "value", "expiration") VALUES ('yualan_pos_hosted_cache_dian@outlook.com|114.10.11.147:timer', 'i:1755400372;', 1755400372);
INSERT INTO "public"."cache" ("key", "value", "expiration") VALUES ('yualan_pos_hosted_cache_dian@outlook.com|114.10.11.147', 'i:1;', 1755400372);
INSERT INTO "public"."cache" ("key", "value", "expiration") VALUES ('yualan_pos_hosted_cache_fa7db8ccad5197e5ed8ef92cb71a0feb10baaa3e:timer', 'i:1755589421;', 1755589421);
INSERT INTO "public"."cache" ("key", "value", "expiration") VALUES ('yualan_pos_hosted_cache_fa7db8ccad5197e5ed8ef92cb71a0feb10baaa3e', 'i:1;', 1755589421);
INSERT INTO "public"."cache" ("key", "value", "expiration") VALUES ('yualan_pos_hosted_cache_anhalim.m@gmail.com|182.8.226.2:timer', 'i:1755597124;', 1755597124);
INSERT INTO "public"."cache" ("key", "value", "expiration") VALUES ('yualan_pos_hosted_cache_anhalim.m@gmail.com|182.8.226.2', 'i:1;', 1755597124);
INSERT INTO "public"."cache" ("key", "value", "expiration") VALUES ('yualan_pos_hosted_cache_sa@example.com|110.137.102.165:timer', 'i:1755628639;', 1755628639);
INSERT INTO "public"."cache" ("key", "value", "expiration") VALUES ('yualan_pos_hosted_cache_sa@example.com|110.137.102.165', 'i:2;', 1755628639);
INSERT INTO "public"."cache" ("key", "value", "expiration") VALUES ('yualan_pos_hosted_cache_admin@gmail.com|37.19.201.207:timer', 'i:1755717266;', 1755717266);
INSERT INTO "public"."cache" ("key", "value", "expiration") VALUES ('yualan_pos_hosted_cache_admin@gmail.com|37.19.201.207', 'i:2;', 1755717266);
INSERT INTO "public"."cache" ("key", "value", "expiration") VALUES ('yualan_pos_hosted_cache_4d4045e93728056b07032cee674859c4ab7dcd3c:timer', 'i:1756572841;', 1756572841);
INSERT INTO "public"."cache" ("key", "value", "expiration") VALUES ('yualan_pos_hosted_cache_4d4045e93728056b07032cee674859c4ab7dcd3c', 'i:2;', 1756572841);
INSERT INTO "public"."users" ("id", "tenant_id", "name", "email", "email_verified_at", "password", "role", "remember_token", "created_at", "updated_at", "deleted_at") VALUES ('19964c44-098e-32dd-948d-8214e1e090cb', '90139248-5a59-37af-bd9f-5570cd380f2f', 'Mr. Super Kece', 'sa@example.com', '2025-08-28 06:56:11', '$2y$12$9sDAXD53jiDg.j1BJKfleuorNLOi/VeGZPlKpbrTEpnRvWksXlGQW', 'superadmin', 'mcNmi4WGFa', '2025-08-28 06:56:11', '2025-08-28 06:56:11', NULL);
INSERT INTO "public"."users" ("id", "tenant_id", "name", "email", "email_verified_at", "password", "role", "remember_token", "created_at", "updated_at", "deleted_at") VALUES ('97715dac-09fa-4b60-845e-3668bb26a0de', '4e691cc0-1156-49c7-aa70-450c9f9c1760', 'Muhaimin', 'muhaimin@gmail.com', '2025-08-30 16:53:09', '$2y$12$4w1nLC1cYGIR/HRft8mvCelMoNnnlAD3P7.GmDWp04AWlJ/3eT24K', 'admin', NULL, '2025-08-30 16:52:06', '2025-08-30 16:53:09', NULL);
INSERT INTO "public"."users" ("id", "tenant_id", "name", "email", "email_verified_at", "password", "role", "remember_token", "created_at", "updated_at", "deleted_at") VALUES ('a3f382f3-9c08-4ba7-b981-2084022a533b', '5b5b91ec-db50-4c06-aa88-5f2bfcd271b7', 'Fajar Nur Prasetyo', 'fajarnurprasetyo.2@gmail.com', NULL, '$2y$12$xR9fq3DAjDKFbtweJ8zCSeeojHtL8iMSYXbBOemL41bDdAM2c5HeW', 'admin', NULL, '2025-08-09 10:09:58', '2025-08-09 10:09:58', NULL);
INSERT INTO "public"."users" ("id", "tenant_id", "name", "email", "email_verified_at", "password", "role", "remember_token", "created_at", "updated_at", "deleted_at") VALUES ('af5c3f65-5aa5-45fe-a126-78fda4938e1d', '57cd59e8-b4f0-4032-8e09-7084a7057e53', 'halim', 'anhalimm@gmail.com', NULL, '$2y$12$4u2BYYQXbZfiQ7rEvVINZO1P45fKnVsRq1zS1P2.HW3GaDun9A9Em', 'admin', NULL, '2025-08-09 14:37:36', '2025-08-09 14:37:36', NULL);
INSERT INTO "public"."users" ("id", "tenant_id", "name", "email", "email_verified_at", "password", "role", "remember_token", "created_at", "updated_at", "deleted_at") VALUES ('9b3372fa-e766-4760-9cb9-487d119985e6', '43120495-d74c-4d15-a502-e56a57fd62b3', 'demotes', 'adeulala5@gmail.com', NULL, '$2y$12$sT8/kkmJWsicxxivn0Typ.t1jWnV20B35fM3oWCCMTvmAKBkvCL6e', 'admin', NULL, '2025-08-11 02:20:27', '2025-08-11 02:20:27', NULL);
INSERT INTO "public"."users" ("id", "tenant_id", "name", "email", "email_verified_at", "password", "role", "remember_token", "created_at", "updated_at", "deleted_at") VALUES ('8294addc-e235-4b01-80ef-f9934afedb21', NULL, 'Mr. Super Kece', 'abdurozzaq.hadi@gmail.com', NULL, '$2y$12$C1RS/MjY7N3kHCK4QvEso.23OB/s2pwMAdQDzmbeJSKsUxvXE3RJ.', 'superadmin', 'GXqmz1MtQGkpCCN6hgQrDO7999mG86rSe8HHt4EAo69wPFMrZwVUvD93nkSM', '2025-08-08 19:55:12', '2025-08-16 13:34:01', NULL);
INSERT INTO "public"."users" ("id", "tenant_id", "name", "email", "email_verified_at", "password", "role", "remember_token", "created_at", "updated_at", "deleted_at") VALUES ('b426e24e-1a35-47fa-9d9e-884d08e1e628', 'f48dc991-f323-4749-8a28-ee45af816024', 'y', 'anhalim.m@gmail.com', NULL, '$2y$12$n3qOt5h5yoFfiQrHlSzpze0cqV4w1ekcE/v4JBBgw26BuQ/mxtWaq', 'admin', NULL, '2025-08-19 09:48:55', '2025-08-19 09:50:57', '2025-08-19 09:50:57');
INSERT INTO "public"."users" ("id", "tenant_id", "name", "email", "email_verified_at", "password", "role", "remember_token", "created_at", "updated_at", "deleted_at") VALUES ('0ed7882b-76d1-4679-920b-9a3af1e673d9', '8a3b1756-349b-41cf-bb5e-bdcaba3f36df', 'Riang Ateka', 'riang.pgk@gmail.com', NULL, '$2y$12$pASkWPXR3SH2Y7BbwNGeNuG.BzYDeS.8Spbo8T26HkRD14f05ii6.', 'admin', NULL, '2025-08-20 00:22:53', '2025-08-20 00:22:53', NULL);
INSERT INTO "public"."users" ("id", "tenant_id", "name", "email", "email_verified_at", "password", "role", "remember_token", "created_at", "updated_at", "deleted_at") VALUES ('ee28793f-791b-4f82-b5bc-540b729bee43', 'cb6c9def-98f6-4a0d-a80e-1ebdedf9627f', 'mpwoid', 'moreplus.wo@gmail.com', NULL, '$2y$12$MTqyHP2sTv4WupIKgBaUPuYZY3zijwmXHP9bW0Tuzw/AEoq9UHIBW', 'admin', NULL, '2025-08-20 13:05:10', '2025-08-20 13:05:10', NULL);
INSERT INTO "public"."users" ("id", "tenant_id", "name", "email", "email_verified_at", "password", "role", "remember_token", "created_at", "updated_at", "deleted_at") VALUES ('07e0eb2d-b88f-4f0a-81ed-5f5770e033ce', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'Abdurozzaq Nurul Hadi', 'abdurozzaq00@gmail.com', NULL, '$2y$12$pcnydzMHs.FTHCaFBy5nbukF.HhCsDRi2ZyYBKSnSdGFhFVfajFMm', 'admin', 'N2ojC5HsE7uP8YBcWscLI7thpdhQJPkD6MVi1SOhIdUEmCWk2xBCERjDglp1', '2025-08-08 20:51:42', '2025-08-08 20:51:42', NULL);
INSERT INTO "public"."users" ("id", "tenant_id", "name", "email", "email_verified_at", "password", "role", "remember_token", "created_at", "updated_at", "deleted_at") VALUES ('cff223a8-ab79-4a5f-906d-762631071e16', '178284e6-b464-42df-adff-e8dae85d4296', 'Abdurozzaq Nurul Hadi', 'abdurozzaq.third@gmail.com', NULL, '$2y$12$B5WKGB1ZRdIBFHztCJxoY.4Jr5GEhYdIDrdnfsyNQD/9.j6/LJ8nG', 'admin', NULL, '2025-08-24 11:38:34', '2025-08-24 11:38:34', NULL);
INSERT INTO "public"."categories" ("id", "tenant_id", "name", "description", "created_at", "updated_at", "deleted_at") VALUES ('98a2de5a-2def-4a54-95a3-626c45ee4d3d', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'Laptop', 'Kategori Laptop', '2025-08-09 05:25:22', '2025-08-09 05:25:22', NULL);
INSERT INTO "public"."categories" ("id", "tenant_id", "name", "description", "created_at", "updated_at", "deleted_at") VALUES ('1514dbef-679c-46c4-8f6f-ca2f143cadb4', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'PC Rakitan', 'Kategori PC Rakitan', '2025-08-09 05:25:35', '2025-08-09 05:25:35', NULL);
INSERT INTO "public"."categories" ("id", "tenant_id", "name", "description", "created_at", "updated_at", "deleted_at") VALUES ('237d4610-9f1a-47e2-85bd-82198e7c3707', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'Mouse', 'Kategori Mouse', '2025-08-09 05:25:47', '2025-08-09 05:25:47', NULL);
INSERT INTO "public"."categories" ("id", "tenant_id", "name", "description", "created_at", "updated_at", "deleted_at") VALUES ('2eca98ec-98fe-4c95-b628-0c650b2500e0', 'cb6c9def-98f6-4a0d-a80e-1ebdedf9627f', 'kategori satu', NULL, '2025-08-20 13:05:52', '2025-08-20 13:05:52', NULL);
INSERT INTO "public"."categories" ("id", "tenant_id", "name", "description", "created_at", "updated_at", "deleted_at") VALUES ('7b76beba-0f3f-4585-b824-90c0acc269b0', 'cb6c9def-98f6-4a0d-a80e-1ebdedf9627f', 'kategori dua', NULL, '2025-08-20 13:06:00', '2025-08-20 13:06:00', NULL);
INSERT INTO "public"."categories" ("id", "tenant_id", "name", "description", "created_at", "updated_at", "deleted_at") VALUES ('dbfdc93a-994c-4b5c-9129-9b7df6c960a2', 'cb6c9def-98f6-4a0d-a80e-1ebdedf9627f', 'kategori tiga', NULL, '2025-08-20 13:06:05', '2025-08-20 13:06:05', NULL);
INSERT INTO "public"."customers" ("id", "tenant_id", "name", "email", "phone", "address", "created_at", "updated_at", "deleted_at") VALUES ('d069c310-8ed0-4842-97d3-12ff0f6a1077', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'Abdurozzaq Nurul Hadi', 'abdurozzaq00@gmail.com', '089603363136', 'Kp. Sentul No. 57 Rt.005 Rw.004', '2025-08-09 05:27:35', '2025-08-09 05:27:35', NULL);
INSERT INTO "public"."customers" ("id", "tenant_id", "name", "email", "phone", "address", "created_at", "updated_at", "deleted_at") VALUES ('dcf27911-1e38-47cd-a073-e5c85db13266', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'Sari Wardani', 'sariwardani4@gmail.com', '089603363136', 'Kp. Sentul No. 57 Rt.005 Rw.004', '2025-08-09 05:27:45', '2025-08-09 05:27:45', NULL);
INSERT INTO "public"."suppliers" ("id", "tenant_id", "name", "contact_person", "phone", "email", "address", "notes", "created_at", "updated_at", "deleted_at") VALUES ('9a6a30fd-9af8-4397-b480-947cf39317c9', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'PT. FBS', 'Jajang Suratang', '089603363136', 'jajang@yopmail.com', 'Kp. Sentul No. 57 Rt.005 Rw.004', NULL, '2025-08-09 05:28:33', '2025-08-09 05:28:33', NULL);
INSERT INTO "public"."suppliers" ("id", "tenant_id", "name", "contact_person", "phone", "email", "address", "notes", "created_at", "updated_at", "deleted_at") VALUES ('f2ba995e-c78e-4825-aa94-b7e3fc964179', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'PT. RIMBA', 'Ade Sudarman', '089603363226', 'sudarman@gmail.com', 'Kp. Jagora No. 544 Rt.005 Rw.004', NULL, '2025-08-09 05:29:13', '2025-08-09 05:29:23', NULL);
INSERT INTO "public"."payments" ("id", "tenant_id", "sale_id", "payment_method", "amount", "currency", "status", "transaction_id", "gateway_response", "notes", "created_at", "updated_at") VALUES ('5662112d-d782-4c5b-b6e2-78c4eef28cf5', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'b24bf096-db7d-442b-82a3-0798ec6e172b', 'ipaymu', '500000.00', 'IDR', 'pending', NULL, '{"Data":{"Url":"https://sandbox-payment.ipaymu.com/#/156645aa-5836-48ef-b858-edd9d55ea359","SessionID":"156645aa-5836-48ef-b858-edd9d55ea359"},"Status":200,"Message":"Success","Success":true}', 'Pembayaran iPaymu diinisiasi', '2025-08-09 06:53:56', '2025-08-09 06:53:56');
INSERT INTO "public"."payments" ("id", "tenant_id", "sale_id", "payment_method", "amount", "currency", "status", "transaction_id", "gateway_response", "notes", "created_at", "updated_at") VALUES ('856bfa2b-2288-4958-8140-d2dc3c2e91fd', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '7781eabb-d889-483d-b914-ca76b10b89ad', 'ipaymu', '1000000.00', 'IDR', 'pending', NULL, '{"Data":{"Url":"https://sandbox-payment.ipaymu.com/#/8d2b77e2-11dd-4f8d-9c74-071569e05a32","SessionID":"8d2b77e2-11dd-4f8d-9c74-071569e05a32"},"Status":200,"Message":"Success","Success":true}', 'Pembayaran iPaymu diinisiasi', '2025-08-09 06:54:58', '2025-08-09 06:54:58');
INSERT INTO "public"."payments" ("id", "tenant_id", "sale_id", "payment_method", "amount", "currency", "status", "transaction_id", "gateway_response", "notes", "created_at", "updated_at") VALUES ('72eed846-0998-40c2-92b5-3546b65448b5', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '7781eabb-d889-483d-b914-ca76b10b89ad', 'ipaymu', '1000000.00', 'IDR', 'completed', '174544', '{"Data":{"Fee":4000,"Type":7,"Notes":null,"Amount":1000000,"Sender":"System","Status":1,"IsLocked":false,"Receiver":"Abdurozzaq Nurul Hadi","SubTotal":1000000,"TypeDesc":"VA & Transfer Bank","BuyerName":"Guest Customer","RelatedId":null,"SessionId":"8d2b77e2-11dd-4f8d-9c74-071569e05a32","BuyerEmail":"guest@example.com","BuyerPhone":"081234567890","PaidStatus":"paid","StatusDesc":"Berhasil","CreatedDate":"2025-08-09 13:55:12","ExpiredDate":"2025-08-10 13:55:12","PaymentCode":"3811800034511804","PaymentName":"Hallo Bali","ReferenceId":"7781eabb-d889-483d-b914-ca76b10b89ad","SuccessDate":"2025-08-09 13:55:22","PaymentMethod":"va","TransactionId":174544,"PaymentChannel":"bca","SettlementDate":"2025-08-09 13:55:22"},"Status":200,"Message":"success","Success":true}', 'Pembayaran iPaymu (IPN)', '2025-08-09 06:55:24', '2025-08-09 06:55:24');
INSERT INTO "public"."payments" ("id", "tenant_id", "sale_id", "payment_method", "amount", "currency", "status", "transaction_id", "gateway_response", "notes", "created_at", "updated_at") VALUES ('31e8887d-b2fd-4ed6-b261-970326b1271a', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'd434dc3e-c3ff-4931-8ded-ef23459459eb', 'ipaymu', '500000.00', 'IDR', 'pending', NULL, '{"Data":{"Url":"https://sandbox-payment.ipaymu.com/#/861ff6fc-8c59-440c-8ab9-979c31d9b73f","SessionID":"861ff6fc-8c59-440c-8ab9-979c31d9b73f"},"Status":200,"Message":"Success","Success":true}', 'Pembayaran iPaymu diinisiasi - TRX ID: null', '2025-08-11 13:48:34', '2025-08-11 13:48:34');
INSERT INTO "public"."payments" ("id", "tenant_id", "sale_id", "payment_method", "amount", "currency", "status", "transaction_id", "gateway_response", "notes", "created_at", "updated_at") VALUES ('463d1983-3ada-43a3-9481-355b7d334093', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', NULL, 'ipaymu', '15374.00', 'IDR', 'completed', '24381553', NULL, NULL, '2025-08-14 04:38:37', '2025-08-14 04:38:37');
INSERT INTO "public"."payments" ("id", "tenant_id", "sale_id", "payment_method", "amount", "currency", "status", "transaction_id", "gateway_response", "notes", "created_at", "updated_at") VALUES ('d5b14ff4-7023-4414-a04d-513b62724cd0', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', NULL, 'ipaymu', '15374.00', 'IDR', 'completed', '24381745', NULL, NULL, '2025-08-14 04:42:49', '2025-08-14 04:42:49');
INSERT INTO "public"."payments" ("id", "tenant_id", "sale_id", "payment_method", "amount", "currency", "status", "transaction_id", "gateway_response", "notes", "created_at", "updated_at") VALUES ('ce5f7fd3-c82a-4c7d-8810-18d691d2f037', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'd434dc3e-c3ff-4931-8ded-ef23459459eb', 'ipaymu', '500000.00', 'IDR', 'pending', NULL, '{"Data":{"Url":"https://sandbox-payment.ipaymu.com/#/0d75ca48-c372-4ca2-9ffe-e7e758809d9c","SessionID":"0d75ca48-c372-4ca2-9ffe-e7e758809d9c"},"Status":200,"Message":"Success","Success":true}', 'Pembayaran iPaymu diinisiasi - TRX ID: null', '2025-08-20 08:08:41', '2025-08-20 08:08:41');
INSERT INTO "public"."payments" ("id", "tenant_id", "sale_id", "payment_method", "amount", "currency", "status", "transaction_id", "gateway_response", "notes", "created_at", "updated_at") VALUES ('9b1cf7a7-3726-40f9-8915-e048dc1fe697', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '0445050a-691d-4b89-bd76-35d5428b0505', 'ipaymu', '170000.00', 'IDR', 'pending', NULL, '{"Data":{"Url":"https://sandbox-payment.ipaymu.com/#/d9d992e3-2973-435c-b434-b3cf3d6af93b","SessionID":"d9d992e3-2973-435c-b434-b3cf3d6af93b"},"Status":200,"Message":"Success","Success":true}', 'Pembayaran iPaymu diinisiasi - TRX ID: null', '2025-08-24 11:25:09', '2025-08-24 11:25:09');
INSERT INTO "public"."payments" ("id", "tenant_id", "sale_id", "payment_method", "amount", "currency", "status", "transaction_id", "gateway_response", "notes", "created_at", "updated_at") VALUES ('09c4eb26-fc13-4063-b333-c7b50bc7e5f4', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'c1787a56-d6f7-4884-989d-ae54effc60d3', 'ipaymu', '170000.00', 'IDR', 'pending', NULL, '{"Data":{"Url":"https://sandbox-payment.ipaymu.com/#/70d42c96-e44e-4305-a30a-2dff9751b4ba","SessionID":"70d42c96-e44e-4305-a30a-2dff9751b4ba"},"Status":200,"Message":"Success","Success":true}', 'Pembayaran iPaymu diinisiasi - TRX ID: null', '2025-08-24 11:25:14', '2025-08-24 11:25:14');
INSERT INTO "public"."payments" ("id", "tenant_id", "sale_id", "payment_method", "amount", "currency", "status", "transaction_id", "gateway_response", "notes", "created_at", "updated_at") VALUES ('86a72bd7-10cc-4b9d-9531-dff3ebf362ff', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '3db056a5-cc7b-4a2e-8d54-12ea1ab372e0', 'ipaymu', '45000.00', 'IDR', 'pending', NULL, '{"Data":{"Url":"https://sandbox-payment.ipaymu.com/#/d7c76fb7-38e0-4012-bb75-4b85cbf59073","SessionID":"d7c76fb7-38e0-4012-bb75-4b85cbf59073"},"Status":200,"Message":"Success","Success":true}', 'Pembayaran iPaymu diinisiasi - TRX ID: null', '2025-08-24 11:28:57', '2025-08-24 11:28:57');
INSERT INTO "public"."payments" ("id", "tenant_id", "sale_id", "payment_method", "amount", "currency", "status", "transaction_id", "gateway_response", "notes", "created_at", "updated_at") VALUES ('04ee00e6-2657-4b9a-9ed4-389414f2d508', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '8435142f-3a2c-4b41-b6f1-fc07a51a52ca', 'ipaymu', '15000.00', 'IDR', 'pending', NULL, '{"Data":{"Url":"https://sandbox-payment.ipaymu.com/#/135ec940-08b0-468e-8bbe-9baf8bb18901","SessionID":"135ec940-08b0-468e-8bbe-9baf8bb18901"},"Status":200,"Message":"Success","Success":true}', 'Pembayaran iPaymu diinisiasi - TRX ID: null', '2025-08-24 11:37:21', '2025-08-24 11:37:21');
INSERT INTO "public"."payments" ("id", "tenant_id", "sale_id", "payment_method", "amount", "currency", "status", "transaction_id", "gateway_response", "notes", "created_at", "updated_at") VALUES ('f5b0ac12-f69c-4b69-aa0c-3b8b39f2ddfb', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'd5fb7dcb-9635-4b58-9f99-42a049b4684e', 'ipaymu', '15000.00', 'IDR', 'pending', NULL, '{"Data":{"Url":"https://sandbox-payment.ipaymu.com/#/bb969c5d-528b-4bc3-b803-bbd50465b976","SessionID":"bb969c5d-528b-4bc3-b803-bbd50465b976"},"Status":200,"Message":"Success","Success":true}', 'Pembayaran iPaymu diinisiasi - TRX ID: null', '2025-08-24 11:49:03', '2025-08-24 11:49:03');
INSERT INTO "public"."payments" ("id", "tenant_id", "sale_id", "payment_method", "amount", "currency", "status", "transaction_id", "gateway_response", "notes", "created_at", "updated_at") VALUES ('7236c179-30e2-49d5-9534-b1a5d41020ad', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '4335850a-8d3d-46f1-8c75-162e3549c115', 'ipaymu', '15000.00', 'IDR', 'pending', NULL, '{"Data":{"Url":"https://sandbox-payment.ipaymu.com/#/e831c428-f379-4275-8dbd-6edfef8cbe5b","SessionID":"e831c428-f379-4275-8dbd-6edfef8cbe5b"},"Status":200,"Message":"Success","Success":true}', 'Pembayaran iPaymu diinisiasi - TRX ID: null', '2025-08-24 11:55:39', '2025-08-24 11:55:39');
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('abd731c8-afda-492c-9f20-ab55af70c23b', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'd23ec59d-83a3-4f09-aeaf-f36badf174d7', 50, 'in', 'Pembelian', NULL, NULL, '2025-08-09 06:06:32', '2025-08-09 06:06:32', '420000.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('f5d75206-d229-4c8f-8f1b-7f1767cd6191', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'f6e9babf-b49c-4835-a27a-fe88fc88acea', 15, 'in', 'Pembelian', NULL, NULL, '2025-08-09 06:06:53', '2025-08-09 06:06:53', '11500000.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('e66c7118-724f-42da-b045-fdb7fbce3390', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'd23ec59d-83a3-4f09-aeaf-f36badf174d7', -3, 'adjustment', 'Rusak karena banjir', NULL, NULL, '2025-08-09 06:08:58', '2025-08-09 06:08:58', '420000.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('9b98fa56-522d-429b-942c-e4653df8ed1e', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'd23ec59d-83a3-4f09-aeaf-f36badf174d7', -1, 'out', 'Penjualan: INV-20250809062330-BJV5', NULL, NULL, '2025-08-09 06:23:30', '2025-08-09 06:23:30', '0.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('0ccb6142-1ab6-49f6-8a53-fd34da33bec0', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'd23ec59d-83a3-4f09-aeaf-f36badf174d7', -1, 'out', 'Penjualan: INV-20250809063441-TTcj', NULL, NULL, '2025-08-09 06:34:41', '2025-08-09 06:34:41', '0.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('bc54fd90-3a41-43d8-a2ec-329ec8ae6001', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'd23ec59d-83a3-4f09-aeaf-f36badf174d7', -1, 'out', 'Penjualan: INV-20250809065354-aYXc', NULL, NULL, '2025-08-09 06:53:54', '2025-08-09 06:53:54', '0.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('fb315af7-82c7-48a6-a613-4c1b206f761e', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'd23ec59d-83a3-4f09-aeaf-f36badf174d7', -2, 'out', 'Penjualan: INV-20250809065457-zSxz', NULL, NULL, '2025-08-09 06:54:57', '2025-08-09 06:54:57', '0.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('34218e6f-f36c-4f68-9fba-89940ce3868c', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'd23ec59d-83a3-4f09-aeaf-f36badf174d7', -2, 'out', 'Penjualan iPaymu: INV-20250809065457-zSxz', NULL, NULL, '2025-08-09 06:55:24', '2025-08-09 06:55:24', '0.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('ea7d9b6b-3920-4380-a810-08ad3fa2e86b', '57cd59e8-b4f0-4032-8e09-7084a7057e53', 'a1c2c01c-8440-40e1-a1ac-700b073a798f', 100, 'in', 'Penerimaan barang dari supplier', NULL, NULL, '2025-08-09 14:42:09', '2025-08-09 14:42:09', '10000.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('f37d559c-8cba-4303-a100-f64c69411e07', '57cd59e8-b4f0-4032-8e09-7084a7057e53', 'a1c2c01c-8440-40e1-a1ac-700b073a798f', -5, 'out', 'Penjualan: INV-20250809144334-adAS', NULL, NULL, '2025-08-09 14:43:34', '2025-08-09 14:43:34', '0.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('3686595d-b593-4a87-a0a8-d8aa4a945908', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'd23ec59d-83a3-4f09-aeaf-f36badf174d7', -1, 'out', 'Penjualan: INV-20250811134832-Sund', NULL, NULL, '2025-08-11 13:48:32', '2025-08-11 13:48:32', '0.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('08d0bf5f-cbce-426c-837b-11ff264acb8c', 'cb6c9def-98f6-4a0d-a80e-1ebdedf9627f', '71eb4c8e-34ec-4844-8c1f-98f92245d9f8', 10, 'adjustment', 'test', NULL, NULL, '2025-08-20 13:08:32', '2025-08-20 13:08:32', '75.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('88ed13e9-0942-4339-bb11-6d894b3cc8a4', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'd5f2ec67-05cb-4a8e-828c-d16352986061', 50, 'in', NULL, NULL, NULL, '2025-08-22 09:04:48', '2025-08-22 09:04:48', '0.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('984f9144-a1c2-41e7-84be-4cef469997cd', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '068381fe-91ba-47f0-92c3-c47baf48a7d0', 30, 'in', NULL, NULL, NULL, '2025-08-22 09:04:48', '2025-08-22 09:04:48', '0.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('233f0a0f-e2a7-4f11-8b1e-ab5d462da15b', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'e36b4b20-faaf-47df-8a60-4170c858ee14', 10, 'in', NULL, NULL, NULL, '2025-08-22 09:04:48', '2025-08-22 09:04:48', '0.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('62b01bd5-a0a3-46fb-920c-7bf9230e5ce0', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'e36b4b20-faaf-47df-8a60-4170c858ee14', -2, 'out', 'Penjualan: INV-20250824112507-CxR9', NULL, NULL, '2025-08-24 11:25:07', '2025-08-24 11:25:07', '0.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('6145bc07-61db-4728-ab8d-64d44d991572', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '068381fe-91ba-47f0-92c3-c47baf48a7d0', -2, 'out', 'Penjualan: INV-20250824112507-CxR9', NULL, NULL, '2025-08-24 11:25:07', '2025-08-24 11:25:07', '0.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('06cd10ac-5de4-424c-9535-b5df8882d3e7', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'd5f2ec67-05cb-4a8e-828c-d16352986061', -2, 'out', 'Penjualan: INV-20250824112507-CxR9', NULL, NULL, '2025-08-24 11:25:07', '2025-08-24 11:25:07', '0.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('dd92191d-61d6-4de9-a6ed-4c2542a8bce2', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'e36b4b20-faaf-47df-8a60-4170c858ee14', -2, 'out', 'Penjualan: INV-20250824112512-B2Qp', NULL, NULL, '2025-08-24 11:25:12', '2025-08-24 11:25:12', '0.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('598525d1-43f5-45e4-85c4-2da9913b0abb', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '068381fe-91ba-47f0-92c3-c47baf48a7d0', -2, 'out', 'Penjualan: INV-20250824112512-B2Qp', NULL, NULL, '2025-08-24 11:25:12', '2025-08-24 11:25:12', '0.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('a9cfcb10-4a7c-4cd3-94cd-22e189105950', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'd5f2ec67-05cb-4a8e-828c-d16352986061', -2, 'out', 'Penjualan: INV-20250824112512-B2Qp', NULL, NULL, '2025-08-24 11:25:12', '2025-08-24 11:25:12', '0.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('c8f03d1c-ae2e-41b4-b711-d6a92b05604f', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'd5f2ec67-05cb-4a8e-828c-d16352986061', -3, 'out', 'Penjualan: INV-20250824112856-9QUw', NULL, NULL, '2025-08-24 11:28:56', '2025-08-24 11:28:56', '0.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('265f18e7-c12f-4985-84cc-c9ff1c6841b8', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'd5f2ec67-05cb-4a8e-828c-d16352986061', -1, 'out', 'Penjualan: INV-20250824113720-lq8x', NULL, NULL, '2025-08-24 11:37:20', '2025-08-24 11:37:20', '0.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('2239cee5-9a18-479c-851b-36c965d0eb71', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'd5f2ec67-05cb-4a8e-828c-d16352986061', -1, 'out', 'Penjualan: INV-20250824114901-rKwv', NULL, NULL, '2025-08-24 11:49:02', '2025-08-24 11:49:02', '0.00', NULL);
INSERT INTO "public"."inventories" ("id", "tenant_id", "product_id", "quantity_change", "type", "reason", "source_id", "source_type", "created_at", "updated_at", "cost_per_unit", "deleted_at") VALUES ('19c3a0db-3975-449c-84ed-732bf8ad0cad', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', 'd5f2ec67-05cb-4a8e-828c-d16352986061', -1, 'out', 'Penjualan: INV-20250824115538-32MR', NULL, NULL, '2025-08-24 11:55:38', '2025-08-24 11:55:38', '0.00', NULL);
INSERT INTO "public"."products" ("id", "tenant_id", "category_id", "name", "sku", "description", "price", "stock", "unit", "image", "is_food_item", "ingredients", "created_at", "updated_at", "deleted_at", "cost_price", "min_stock_level") VALUES ('f6e9babf-b49c-4835-a27a-fe88fc88acea', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '98a2de5a-2def-4a54-95a3-626c45ee4d3d', 'Asus TUF F15 FX506HC', 'ASUS_FX506HC', NULL, '13500000.00', 15, 'Unit', NULL, false, NULL, '2025-08-09 05:26:51', '2025-08-09 06:06:53', NULL, '11500000.00', NULL);
INSERT INTO "public"."products" ("id", "tenant_id", "category_id", "name", "sku", "description", "price", "stock", "unit", "image", "is_food_item", "ingredients", "created_at", "updated_at", "deleted_at", "cost_price", "min_stock_level") VALUES ('9f17326e-b800-41ce-b99b-98d0203eeb2c', '5b5b91ec-db50-4c06-aa88-5f2bfcd271b7', NULL, 'a', NULL, NULL, '0.00', 0, 'kg', NULL, false, NULL, '2025-08-09 10:10:43', '2025-08-09 10:10:43', NULL, '0.00', NULL);
INSERT INTO "public"."products" ("id", "tenant_id", "category_id", "name", "sku", "description", "price", "stock", "unit", "image", "is_food_item", "ingredients", "created_at", "updated_at", "deleted_at", "cost_price", "min_stock_level") VALUES ('a1c2c01c-8440-40e1-a1ac-700b073a798f', '57cd59e8-b4f0-4032-8e09-7084a7057e53', NULL, 'Es teh', NULL, NULL, '20000.00', 95, 'pcs', NULL, false, NULL, '2025-08-09 14:41:37', '2025-08-09 14:43:34', NULL, '10000.00', NULL);
INSERT INTO "public"."products" ("id", "tenant_id", "category_id", "name", "sku", "description", "price", "stock", "unit", "image", "is_food_item", "ingredients", "created_at", "updated_at", "deleted_at", "cost_price", "min_stock_level") VALUES ('d23ec59d-83a3-4f09-aeaf-f36badf174d7', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', '237d4610-9f1a-47e2-85bd-82198e7c3707', 'Mouse Gaming V1', 'MOUSE_V1', NULL, '500000.00', 46, 'Unit', NULL, false, NULL, '2025-08-09 05:27:13', '2025-08-11 13:48:32', NULL, '420000.00', NULL);
INSERT INTO "public"."products" ("id", "tenant_id", "category_id", "name", "sku", "description", "price", "stock", "unit", "image", "is_food_item", "ingredients", "created_at", "updated_at", "deleted_at", "cost_price", "min_stock_level") VALUES ('71eb4c8e-34ec-4844-8c1f-98f92245d9f8', 'cb6c9def-98f6-4a0d-a80e-1ebdedf9627f', '2eca98ec-98fe-4c95-b628-0c650b2500e0', 'produk1 kat1', 'sku002', NULL, '10000.00', 10, 'pcs', NULL, false, NULL, '2025-08-20 13:07:59', '2025-08-20 13:08:32', NULL, '75.00', NULL);
INSERT INTO "public"."products" ("id", "tenant_id", "category_id", "name", "sku", "description", "price", "stock", "unit", "image", "is_food_item", "ingredients", "created_at", "updated_at", "deleted_at", "cost_price", "min_stock_level") VALUES ('e36b4b20-faaf-47df-8a60-4170c858ee14', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', NULL, 'Produk C', 'SKU003', NULL, '50000.00', 6, 'box', NULL, false, NULL, '2025-08-22 09:04:48', '2025-08-24 11:25:12', NULL, '40000.00', NULL);
INSERT INTO "public"."products" ("id", "tenant_id", "category_id", "name", "sku", "description", "price", "stock", "unit", "image", "is_food_item", "ingredients", "created_at", "updated_at", "deleted_at", "cost_price", "min_stock_level") VALUES ('068381fe-91ba-47f0-92c3-c47baf48a7d0', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', NULL, 'Produk B', 'SKU002', NULL, '20000.00', 26, 'pcs', NULL, false, NULL, '2025-08-16 13:28:32', '2025-08-24 11:25:12', NULL, '12000.00', NULL);
INSERT INTO "public"."products" ("id", "tenant_id", "category_id", "name", "sku", "description", "price", "stock", "unit", "image", "is_food_item", "ingredients", "created_at", "updated_at", "deleted_at", "cost_price", "min_stock_level") VALUES ('d5f2ec67-05cb-4a8e-828c-d16352986061', '8dd21e8e-f4ee-49a9-93f5-abd7f4bc502c', NULL, 'Produk A', 'SKU001', NULL, '15000.00', 40, 'pcs', NULL, false, NULL, '2025-08-22 09:04:48', '2025-08-24 11:55:38', NULL, '10000.00', NULL);
INSERT INTO "public"."sale_items" ("id", "sale_id", "product_id", "quantity", "price", "subtotal", "created_at", "updated_at", "cost_price_at_sale") VALUES ('88f6df63-78d5-4a28-a00c-b16ef03706b8', '97d80cf2-0c4a-4eb7-ae7c-89efaafefe92', 'd23ec59d-83a3-4f09-aeaf-f36badf174d7', 1, '500000.00', '500000.00', '2025-08-09 06:23:30', '2025-08-09 06:23:30', '0.00');
INSERT INTO "public"."sale_items" ("id", "sale_id", "product_id", "quantity", "price", "subtotal", "created_at", "updated_at", "cost_price_at_sale") VALUES ('b48c79ee-7704-47d9-865f-170586cf8600', 'dd372ee8-3d4f-49d8-b661-aef7c1c5f504', 'd23ec59d-83a3-4f09-aeaf-f36badf174d7', 1, '500000.00', '500000.00', '2025-08-09 06:34:41', '2025-08-09 06:34:41', '0.00');
INSERT INTO "public"."sale_items" ("id", "sale_id", "product_id", "quantity", "price", "subtotal", "created_at", "updated_at", "cost_price_at_sale") VALUES ('40aa6f4d-3685-4aa2-9763-229bf95d2da5', 'b24bf096-db7d-442b-82a3-0798ec6e172b', 'd23ec59d-83a3-4f09-aeaf-f36badf174d7', 1, '500000.00', '500000.00', '2025-08-09 06:53:54', '2025-08-09 06:53:54', '0.00');
INSERT INTO "public"."sale_items" ("id", "sale_id", "product_id", "quantity", "price", "subtotal", "created_at", "updated_at", "cost_price_at_sale") VALUES ('318827e9-34c8-46d1-a9e9-b36be985bad6', '7781eabb-d889-483d-b914-ca76b10b89ad', 'd23ec59d-83a3-4f09-aeaf-f36badf174d7', 2, '500000.00', '1000000.00', '2025-08-09 06:54:57', '2025-08-09 06:54:57', '0.00');
INSERT INTO "public"."sale_items" ("id", "sale_id", "product_id", "quantity", "price", "subtotal", "created_at", "updated_at", "cost_price_at_sale") VALUES ('f7923ab0-f7fe-4111-8802-7fb467ba368b', '82ad3aae-f3ea-405f-ab73-75488c11a8ea', 'a1c2c01c-8440-40e1-a1ac-700b073a798f', 5, '20000.00', '100000.00', '2025-08-09 14:43:34', '2025-08-09 14:43:34', '0.00');
INSERT INTO "public"."sale_items" ("id", "sale_id", "product_id", "quantity", "price", "subtotal", "created_at", "updated_at", "cost_price_at_sale") VALUES ('8811e6bb-8300-4856-a3a2-84fd37d71fa5', 'd434dc3e-c3ff-4931-8ded-ef23459459eb', 'd23ec59d-83a3-4f09-aeaf-f36badf174d7', 1, '500000.00', '500000.00', '2025-08-11 13:48:32', '2025-08-11 13:48:32', '0.00');
INSERT INTO "public"."sale_items" ("id", "sale_id", "product_id", "quantity", "price", "subtotal", "created_at", "updated_at", "cost_price_at_sale") VALUES ('ff44a184-38ae-4fb4-ab38-7e34642ceadf', '0445050a-691d-4b89-bd76-35d5428b0505', 'e36b4b20-faaf-47df-8a60-4170c858ee14', 2, '50000.00', '100000.00', '2025-08-24 11:25:07', '2025-08-24 11:25:07', '40000.00');
INSERT INTO "public"."sale_items" ("id", "sale_id", "product_id", "quantity", "price", "subtotal", "created_at", "updated_at", "cost_price_at_sale") VALUES ('66e79f1a-f854-4492-90fa-081cfa5713b3', '0445050a-691d-4b89-bd76-35d5428b0505', '068381fe-91ba-47f0-92c3-c47baf48a7d0', 2, '20000.00', '40000.00', '2025-08-24 11:25:07', '2025-08-24 11:25:07', '12000.00');
INSERT INTO "public"."sale_items" ("id", "sale_id", "product_id", "quantity", "price", "subtotal", "created_at", "updated_at", "cost_price_at_sale") VALUES ('20fec4dc-10ac-4894-9b85-fcccfcea8593', '0445050a-691d-4b89-bd76-35d5428b0505', 'd5f2ec67-05cb-4a8e-828c-d16352986061', 2, '15000.00', '30000.00', '2025-08-24 11:25:07', '2025-08-24 11:25:07', '10000.00');
INSERT INTO "public"."sale_items" ("id", "sale_id", "product_id", "quantity", "price", "subtotal", "created_at", "updated_at", "cost_price_at_sale") VALUES ('cbf56631-88e4-45c4-8445-6467d0fbc249', 'c1787a56-d6f7-4884-989d-ae54effc60d3', 'e36b4b20-faaf-47df-8a60-4170c858ee14', 2, '50000.00', '100000.00', '2025-08-24 11:25:12', '2025-08-24 11:25:12', '40000.00');
INSERT INTO "public"."sale_items" ("id", "sale_id", "product_id", "quantity", "price", "subtotal", "created_at", "updated_at", "cost_price_at_sale") VALUES ('2bdb237f-869b-49d4-b424-61f2731d5520', 'c1787a56-d6f7-4884-989d-ae54effc60d3', '068381fe-91ba-47f0-92c3-c47baf48a7d0', 2, '20000.00', '40000.00', '2025-08-24 11:25:12', '2025-08-24 11:25:12', '12000.00');
INSERT INTO "public"."sale_items" ("id", "sale_id", "product_id", "quantity", "price", "subtotal", "created_at", "updated_at", "cost_price_at_sale") VALUES ('60f86bee-8d13-4e70-b3d1-5decf2ad1f9f', 'c1787a56-d6f7-4884-989d-ae54effc60d3', 'd5f2ec67-05cb-4a8e-828c-d16352986061', 2, '15000.00', '30000.00', '2025-08-24 11:25:12', '2025-08-24 11:25:12', '10000.00');
INSERT INTO "public"."sale_items" ("id", "sale_id", "product_id", "quantity", "price", "subtotal", "created_at", "updated_at", "cost_price_at_sale") VALUES ('26ceb7f0-87d4-44b7-9e26-7ff8ee729b55', '3db056a5-cc7b-4a2e-8d54-12ea1ab372e0', 'd5f2ec67-05cb-4a8e-828c-d16352986061', 3, '15000.00', '45000.00', '2025-08-24 11:28:56', '2025-08-24 11:28:56', '10000.00');
INSERT INTO "public"."sale_items" ("id", "sale_id", "product_id", "quantity", "price", "subtotal", "created_at", "updated_at", "cost_price_at_sale") VALUES ('9ab20000-400c-4a97-97a2-c9a301487a8c', '8435142f-3a2c-4b41-b6f1-fc07a51a52ca', 'd5f2ec67-05cb-4a8e-828c-d16352986061', 1, '15000.00', '15000.00', '2025-08-24 11:37:20', '2025-08-24 11:37:20', '10000.00');
INSERT INTO "public"."sale_items" ("id", "sale_id", "product_id", "quantity", "price", "subtotal", "created_at", "updated_at", "cost_price_at_sale") VALUES ('3f148fbc-82a4-41ba-8004-2a4715863982', 'd5fb7dcb-9635-4b58-9f99-42a049b4684e', 'd5f2ec67-05cb-4a8e-828c-d16352986061', 1, '15000.00', '15000.00', '2025-08-24 11:49:01', '2025-08-24 11:49:01', '10000.00');
INSERT INTO "public"."sale_items" ("id", "sale_id", "product_id", "quantity", "price", "subtotal", "created_at", "updated_at", "cost_price_at_sale") VALUES ('7c4fb8f1-93ac-4328-a9cf-0bec7ca38fbd', '4335850a-8d3d-46f1-8c75-162e3549c115', 'd5f2ec67-05cb-4a8e-828c-d16352986061', 1, '15000.00', '15000.00', '2025-08-24 11:55:38', '2025-08-24 11:55:38', '10000.00');
INSERT INTO "public"."pricing_plans" ("id", "plan_name", "plan_description", "period_type", "price", "discount_percentage", "created_at", "updated_at") VALUES ('2b3675ce-5467-4825-8ad3-20884121c75f', 'Paket 1 Bulan Full Akses', 'Paket 1 Bulan Full Akses', 'monthly', '75000.00', '60.00', '2025-08-08 20:09:15', '2025-08-14 05:12:55');
INSERT INTO "public"."pricing_plans" ("id", "plan_name", "plan_description", "period_type", "price", "discount_percentage", "created_at", "updated_at") VALUES ('109edce5-71a0-49db-8d15-bd2d5cd1007d', 'Paket 3 Bulan Full Akses', 'Paket 3 Bulan Full Akses', 'quarterly', '225000.00', '65.00', '2025-08-08 20:10:03', '2025-08-14 05:20:20');
INSERT INTO "public"."pricing_plans" ("id", "plan_name", "plan_description", "period_type", "price", "discount_percentage", "created_at", "updated_at") VALUES ('effdde46-2dd6-4834-b4f0-cbcd53767645', 'Paket 1 Tahun Full Akses', 'Paket 1 Tahun Full Akses', 'yearly', '900000.00', '75.00', '2025-08-08 20:10:38', '2025-08-14 05:20:27');
ALTER TABLE "public"."password_reset_tokens" ENABLE TRIGGER ALL;
ALTER TABLE "public"."vouchers" ENABLE TRIGGER ALL;
ALTER TABLE "public"."promos" ENABLE TRIGGER ALL;
ALTER TABLE "public"."tenants" ENABLE TRIGGER ALL;
ALTER TABLE "public"."sales" ENABLE TRIGGER ALL;
ALTER TABLE "public"."saas_settings" ENABLE TRIGGER ALL;
ALTER TABLE "public"."saas_invoices" ENABLE TRIGGER ALL;
ALTER TABLE "public"."migrations" ENABLE TRIGGER ALL;
ALTER TABLE "public"."cache" ENABLE TRIGGER ALL;
ALTER TABLE "public"."cache_locks" ENABLE TRIGGER ALL;
ALTER TABLE "public"."jobs" ENABLE TRIGGER ALL;
ALTER TABLE "public"."job_batches" ENABLE TRIGGER ALL;
ALTER TABLE "public"."failed_jobs" ENABLE TRIGGER ALL;
ALTER TABLE "public"."users" ENABLE TRIGGER ALL;
ALTER TABLE "public"."categories" ENABLE TRIGGER ALL;
ALTER TABLE "public"."customers" ENABLE TRIGGER ALL;
ALTER TABLE "public"."tables" ENABLE TRIGGER ALL;
ALTER TABLE "public"."suppliers" ENABLE TRIGGER ALL;
ALTER TABLE "public"."payments" ENABLE TRIGGER ALL;
ALTER TABLE "public"."inventories" ENABLE TRIGGER ALL;
ALTER TABLE "public"."products" ENABLE TRIGGER ALL;
ALTER TABLE "public"."sale_items" ENABLE TRIGGER ALL;
ALTER TABLE "public"."pricing_plans" ENABLE TRIGGER ALL;
ALTER TABLE "public"."vouchers" ADD CONSTRAINT "vouchers_tenant_id_foreign" FOREIGN KEY ("tenant_id") REFERENCES "public"."tenants" ("id");
ALTER TABLE "public"."promos" ADD CONSTRAINT "promos_tenant_id_foreign" FOREIGN KEY ("tenant_id") REFERENCES "public"."tenants" ("id");
ALTER TABLE "public"."sales" ADD CONSTRAINT "sales_tenant_id_foreign" FOREIGN KEY ("tenant_id") REFERENCES "public"."tenants" ("id");
ALTER TABLE "public"."sales" ADD CONSTRAINT "sales_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "public"."users" ("id");
ALTER TABLE "public"."sales" ADD CONSTRAINT "sales_customer_id_foreign" FOREIGN KEY ("customer_id") REFERENCES "public"."customers" ("id");
ALTER TABLE "public"."saas_invoices" ADD CONSTRAINT "saas_invoices_tenant_id_foreign" FOREIGN KEY ("tenant_id") REFERENCES "public"."tenants" ("id");
ALTER TABLE "public"."users" ADD CONSTRAINT "users_tenant_id_foreign" FOREIGN KEY ("tenant_id") REFERENCES "public"."tenants" ("id");
ALTER TABLE "public"."categories" ADD CONSTRAINT "categories_tenant_id_foreign" FOREIGN KEY ("tenant_id") REFERENCES "public"."tenants" ("id");
ALTER TABLE "public"."customers" ADD CONSTRAINT "customers_tenant_id_foreign" FOREIGN KEY ("tenant_id") REFERENCES "public"."tenants" ("id");
ALTER TABLE "public"."tables" ADD CONSTRAINT "tables_tenant_id_foreign" FOREIGN KEY ("tenant_id") REFERENCES "public"."tenants" ("id");
ALTER TABLE "public"."suppliers" ADD CONSTRAINT "suppliers_tenant_id_foreign" FOREIGN KEY ("tenant_id") REFERENCES "public"."tenants" ("id");
ALTER TABLE "public"."payments" ADD CONSTRAINT "payments_tenant_id_foreign" FOREIGN KEY ("tenant_id") REFERENCES "public"."tenants" ("id");
ALTER TABLE "public"."payments" ADD CONSTRAINT "payments_sale_id_foreign" FOREIGN KEY ("sale_id") REFERENCES "public"."sales" ("id");
ALTER TABLE "public"."inventories" ADD CONSTRAINT "inventories_tenant_id_foreign" FOREIGN KEY ("tenant_id") REFERENCES "public"."tenants" ("id");
ALTER TABLE "public"."inventories" ADD CONSTRAINT "inventories_product_id_foreign" FOREIGN KEY ("product_id") REFERENCES "public"."products" ("id");
ALTER TABLE "public"."products" ADD CONSTRAINT "products_tenant_id_foreign" FOREIGN KEY ("tenant_id") REFERENCES "public"."tenants" ("id");
ALTER TABLE "public"."products" ADD CONSTRAINT "products_category_id_foreign" FOREIGN KEY ("category_id") REFERENCES "public"."categories" ("id");
ALTER TABLE "public"."sale_items" ADD CONSTRAINT "sale_items_sale_id_foreign" FOREIGN KEY ("sale_id") REFERENCES "public"."sales" ("id");
ALTER TABLE "public"."sale_items" ADD CONSTRAINT "sale_items_product_id_foreign" FOREIGN KEY ("product_id") REFERENCES "public"."products" ("id");
