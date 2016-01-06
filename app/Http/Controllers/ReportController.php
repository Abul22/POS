<?php

namespace App\Http\Controllers;

use App\Jobs\SendDebtorEmail;
use App\Report\TrialBalanceReport;
use App\User;
use App\Http\Requests;

use App\Report\StatsReport;
use Vsmoraes\Pdf\PdfFacade as PDF;

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
     */
    public function trialBalance()
    {
        $report = new TrialBalanceReport();

        return view('reports.report.trial-balance', compact('report'));
    }

    /**
     * Generate the lists
     */
    public function lists()
    {
        $users = User::with(['sales.product', 'payments'])->get();

        $usersTransactions = collect();

        foreach($users as $user)
        {
            $transactions = collect();

            $transactions->push([
                'description' => 'Balance brought over',
                'date' => $user->created_at,
                'debit' => null,
                'credit' => null
            ]);

            foreach($user->sales as $sale)
            {
                $transactions->push([
                    'description' => $sale->product->name,
                    'date' => $sale->created_at,
                    'debit' => $sale->total(),
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

                $finalTransactions->push($transaction);
            }

            $usersTransactions->put($user->name, $finalTransactions);
        }

        $html = view('reports.report.lists.lists', compact('usersTransactions'))->render();

        return PDF::load($html)->show();
    }
}
