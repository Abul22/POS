<?php

namespace App\Http\Controllers;

use App\Bank;
use App\Http\Requests\Report\BankRequest;
use App\Http\Requests\Report\GetListsRequest;
use App\Http\Requests\Report\TrialBalanceRequest;
use App\Http\Requests\Report\WithdrawRequest;
use App\Jobs\SendDebtorEmail;
use App\Report\TrialBalanceReport;
use App\User;
use App\Http\Requests;

use App\Report\StatsReport;
use Carbon\Carbon;

use PDF;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('reports.index');
    }

    /**
     * Display the stats report
     */
    public function stats()
    {
        $report = new StatsReport();

        return view('reports.report.stats', compact('report'));
    }

    /**
     * Display all the debtors
     */
    public function debtors()
    {
        $users = User::hasDebt()->sortable()->get();

        return view('reports.report.debtors', compact('users'));
    }

    /**
     * Send email to debtors
     */
    public function sendDebtorEmail()
    {
        $users = User::hasDebt()->sortable()->get();

        foreach($users as $user)
        {
            $this->dispatch(new SendDebtorEmail($user));
        }

        return redirect()->route('report.debtors')->with('success', 'Emails sent');
    }

    /**
     * Generate the trial balance
     * @param TrialBalanceRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function trialBalance(TrialBalanceRequest $request)
    {
        $report = new TrialBalanceReport($request->from, $request->to);

        if($report->from->gt($report->to))
        {
            return redirect()->route('report.trial-balance')
                ->withErrors('From date should be before to date')
                ->withInput();
        }

        return view('reports.report.trial-balance', compact('report'));
    }

    /**
     * Bank an amount
     *
     * @param BankRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bank(BankRequest $request)
    {
        Bank::create([
            'amount' => $request->amountToBank
        ]);

        return redirect()->route('report.trial-balance')->with('success', 'Amount Banked');
    }

    /**
     * Bank an amount
     *
     * @param WithdrawRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function withdraw(WithdrawRequest $request)
    {
        Bank::create([
            'amount' => -1 * $request->amountToWithdraw
        ]);

        return redirect()->route('report.trial-balance')->with('success', 'Amount Withdrawn');
    }

    /**
     * Generate the lists
     * @param GetListsRequest $request
     * @return
     */
    public function lists(GetListsRequest $request)
    {
        if(!$request->has('date'))
        {
            return view('reports.report.lists.lists-select');
        }
        else
        {
            $dateOfReport = new Carbon($request->date);

            $users = User::with(['sales.product', 'payments'])->orderBy('name')->get();

            $usersTransactions = collect();

            foreach($users as $user)
            {
                $transactions = collect();

                $transactions->push([
                    'description' => 'Balance brought over',
                    'date' => $dateOfReport,
                    'debit' => null,
                    'credit' => null
                ]);

                foreach($user->sales as $sale)
                {
                    $transactions->push([
                        'description' => $sale->product->name,
                        'date' => $sale->created_at,
                        'debit' => $sale->total,
                        'credit' => null
                    ]);
                }

                foreach($user->payments as $payment)
                {
                    $amount = $payment->amount;

                    if($amount >= 0)
                    {
                        $transactions->push([
                            'description' => 'Payment made',
                            'date' => $payment->created_at,
                            'debit' => null,
                            'credit' => $amount
                        ]);
                    }
                    else
                    {
                        $transactions->push([
                            'description' => 'Amount loaned',
                            'date' => $payment->created_at,
                            'debit' => abs($amount),
                            'credit' => null
                        ]);
                    }
                }

                $transactions = $transactions->sortBy('date');
                //dd($transactions);
                $finalTransactions = collect();

                $balance = $user->initial_balance;

                foreach($transactions as $transaction)
                {
                    if(!empty($transaction['debit']))
                    {
                        $balance -= $transaction['debit'];
                    }
                    else if(!empty($transaction['credit']))
                    {
                        $balance += $transaction['credit'];
                    }

                    $transaction = collect($transaction);

                    $transaction->put('balance', $balance);

                    if($dateOfReport->year == $transaction['date']->year &&
                        $dateOfReport->month == $transaction['date']->month)
                        $finalTransactions->push($transaction);
                }

                $usersTransactions->put($user->name, $finalTransactions);
            }

            $pdf = PDF::loadView('reports.report.lists.lists', compact('usersTransactions', 'dateOfReport'));

            return $pdf->stream('Lists.pdf');
        }
    }
}
