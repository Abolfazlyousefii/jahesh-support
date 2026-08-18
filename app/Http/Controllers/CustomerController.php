<?php

namespace App\Http\Controllers;

use App\Actions\Customers\CreateCustomerAction;
use App\Actions\Customers\UpdateCustomerAction;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\Ticket;
use App\Services\Finance\CustomerFinanceService;
use App\Support\PhoneNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $status = $request->string('status')->toString();
        $status = in_array($status, ['active', 'inactive'], true) ? $status : 'all';
        $normalizedSearch = PhoneNormalizer::normalize($search);

        $customers = Customer::query()
            ->with('primaryPhone')
            ->when($search !== '', function (Builder $query) use ($search, $normalizedSearch) {
                $query->where(function (Builder $query) use ($search, $normalizedSearch) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->when($normalizedSearch !== '', fn (Builder $query) => $query->orWhereHas(
                            'phones',
                            fn (Builder $phones) => $phones->where('phone', 'like', "%{$normalizedSearch}%"),
                        ));
                });
            })
            ->when($status === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', compact('customers', 'search', 'status'));
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(StoreCustomerRequest $request, CreateCustomerAction $action): RedirectResponse
    {
        $customer = $action->execute($request->validated());

        return redirect()->route('customers.show', $customer)->with('success', 'مشتری با موفقیت ثبت شد.');
    }

    public function show(Request $request, Customer $customer, CustomerFinanceService $finance): View
    {
        $customer->load('phones');
        $recentTickets = $request->user()->can('tickets.view')
            ? Ticket::query()->visibleTo($request->user())->where('customer_id', $customer->id)->latest()->limit(5)->get()
            : collect();

        $financeSummary = $request->user()->can('finance.view') ? $finance->summary($customer) : null;
        $recentLedgerEntries = $financeSummary !== null
            ? $customer->ledgerEntries()->with('creator')->latest('entry_date')->latest('id')->limit(5)->get()
            : collect();

        return view('customers.show', compact('customer', 'recentTickets', 'financeSummary', 'recentLedgerEntries'));
    }

    public function edit(Customer $customer): View
    {
        $customer->load('phones');

        return view('customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer, UpdateCustomerAction $action): RedirectResponse
    {
        $action->execute($customer, $request->validated());

        return redirect()->route('customers.show', $customer)->with('success', 'اطلاعات مشتری به‌روزرسانی شد.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'مشتری حذف شد.');
    }
}
