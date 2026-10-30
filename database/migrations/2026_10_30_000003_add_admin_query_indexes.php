<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Idempoten: sebagian index mungkin sudah dibuat pada percobaan sebelumnya.
        DB::statement('CREATE INDEX IF NOT EXISTS admin_audit_logs_action_created_at_index ON admin_audit_logs (action, created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS admin_audit_logs_created_at_index ON admin_audit_logs (created_at)');

        DB::statement('CREATE INDEX IF NOT EXISTS activity_logs_status_created_at_index ON activity_logs (status, created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS activity_logs_created_at_index ON activity_logs (created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS activity_logs_log_name_index ON activity_logs (log_name)');

        DB::statement('CREATE INDEX IF NOT EXISTS users_created_at_index ON users (created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS users_email_verified_at_index ON users (email_verified_at)');

        // Pencarian admin memakai ILIKE '%term%' — index trigram mempercepatnya.
        // pg_trgm sudah aktif (dipakai transaction_logs).
        DB::statement('CREATE INDEX IF NOT EXISTS users_name_trgm_index ON users USING gin (name gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS users_email_trgm_index ON users USING gin (email gin_trgm_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_name_trgm_index');
        DB::statement('DROP INDEX IF EXISTS users_email_trgm_index');
        DB::statement('DROP INDEX IF EXISTS users_created_at_index');
        DB::statement('DROP INDEX IF EXISTS users_email_verified_at_index');
        DB::statement('DROP INDEX IF EXISTS activity_logs_status_created_at_index');
        DB::statement('DROP INDEX IF EXISTS activity_logs_created_at_index');
        DB::statement('DROP INDEX IF EXISTS activity_logs_log_name_index');
        DB::statement('DROP INDEX IF EXISTS admin_audit_logs_action_created_at_index');
        DB::statement('DROP INDEX IF EXISTS admin_audit_logs_created_at_index');
    }
};
