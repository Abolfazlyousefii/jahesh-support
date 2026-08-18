<?php

use App\Enums\TicketMessageType;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('last_customer_message_at')->nullable()->index();
            $table->timestamp('last_staff_message_at')->nullable()->index();
            $table->timestamp('customer_last_read_at')->nullable();
            $table->timestamp('assignee_last_read_at')->nullable();
        });

        // برای تیکت‌های موجود، زمان آخرین پیام عمومی هر سمت را یک‌بار بازسازی می‌کنیم.
        $customerMorph = (new Customer)->getMorphClass();
        $userMorph = (new User)->getMorphClass();

        DB::table('tickets')->select('id')->orderBy('id')->chunkById(100, function ($tickets) use ($customerMorph, $userMorph) {
            foreach ($tickets as $ticket) {
                $lastCustomerMessageAt = DB::table('ticket_messages')
                    ->where('ticket_id', $ticket->id)
                    ->where('message_type', TicketMessageType::Public->value)
                    ->where('author_type', $customerMorph)
                    ->max('created_at');

                $lastStaffMessageAt = DB::table('ticket_messages')
                    ->where('ticket_id', $ticket->id)
                    ->where('message_type', TicketMessageType::Public->value)
                    ->where('author_type', $userMorph)
                    ->max('created_at');

                DB::table('tickets')->where('id', $ticket->id)->update([
                    'last_customer_message_at' => $lastCustomerMessageAt,
                    'last_staff_message_at' => $lastStaffMessageAt,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'last_customer_message_at',
                'last_staff_message_at',
                'customer_last_read_at',
                'assignee_last_read_at',
            ]);
        });
    }
};
