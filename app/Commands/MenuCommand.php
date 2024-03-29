<?php

namespace App\Commands;

use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Storage;
use LaravelZero\Framework\Commands\Command;
use PhpSchool\CliMenu\Builder\CliMenuBuilder;
use PhpSchool\CliMenu\CliMenu;
use PhpSchool\CliMenu\MenuItem\CheckboxItem;
use PhpSchool\CliMenu\MenuItem\SelectableItem;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\suggest;
use function Laravel\Prompts\text;

class MenuCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'home';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Banking application menu';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $menu = (new CliMenuBuilder())
            ->setTitle('Banking Application CLI')
            ->build();

        $this->buildMenu($menu);
        $menu->open();
    }

    protected function buildMenu(CliMenu $menu)
    {
        $menu->addItems(
            [
                new SelectableItem('Create a new account', [$this, 'addNewAccount']),
                new SelectableItem('Display all accounts', [$this, 'displayAllAccounts']),
                new SelectableItem('Update an account', [$this, 'updateAccount']),
                new SelectableItem('Delete an account', [$this, 'deleteAccount']),
                new SelectableItem('Deposit an amount into your account', [$this, 'depositAmount']),
                new SelectableItem('Withdraw an amount from your account', [$this, 'withdrawAmount']),
                new SelectableItem('Search for account', [$this, 'searchAccount']),
            ]
        );

    }

    public function addNewAccount(CliMenu $menu)
    {
        list($filePath, $accounts) = $this->getAccounts();

        $name = text(
            label: 'What is your name?',
            required: 'Your name is required.'
        );


        $accountType = select(
            'What role should the user have?',
            ['Current Account', 'Saving Account', 'Salary Account'],
        );

        $accountNumber = text(
            label: 'Enter a unique Account Number',
            validate: fn ( $value) => $this->validateAccountNumber($value, $accounts) ? 'Already Exists' : null,
        );

        $balance = text(
            label: 'Enter Balance Amount',
            validate: fn ( $value) => !is_numeric($value) ? 'Enter Valid Amount' : null
        );

        $created_at = Carbon::now();


        $account = [ 'name' => $name, 'account_number' => $accountNumber, 'account_type' => $accountType, 'created_at' => $created_at, 'balance' => $balance];




        $accounts[] = $account;
        file_put_contents($filePath, serialize($accounts));

        $this->info('Account Created Successfully!');
        $this->goToMenu($menu);

    }

    public function displayAllAccounts(CliMenu $menu)
    {
        $filePath = storage_path('accounts.txt');
        $file = 'accounts.txt';

        $accounts = (Storage::exists($file) && Storage::size($file) > 0) ? unserialize(file_get_contents($filePath)) : [];

        $this->displayList($accounts, $menu);
    }

    public function updateAccount(CliMenu $menu)
    {
        list($filePath, $accounts) = $this->getAccounts();

        $accountNumber = $this->getAccount($accounts);

        $defaultName = collect($accounts)->where('account_number', $accountNumber)->first()['name'] ?? null;

        $updateName = text(
            label: 'Enter your name for update!',
            default: $defaultName ?? '',
            required: 'Your name is required.',
        );

        foreach ($accounts as $index => $account){
            if ($account['account_number'] == $accountNumber){
                $accounts[$index]['name'] = $updateName;
            }
        }

        file_put_contents($filePath, serialize($accounts));

        $this->info('Account Updated Successfully!');
        $this->goToMenu($menu);

    }

    public function deleteAccount(CliMenu $menu)
    {
        list($filePath, $accounts) = $this->getAccounts();
        $accountNumber = $this->getAccount($accounts);

        foreach ($accounts as $index => $account){
            if ($account['account_number'] == $accountNumber){
                unset($accounts[$index]);
            }
        }

        file_put_contents($filePath, serialize($accounts));

        $this->info('Account Deleted Successfully!');
        $this->goToMenu($menu);
    }

    public function depositAmount(CliMenu $menu)
    {
        list($filePath, $accounts) = $this->getAccounts();

        $accountNumber = $this->getAccount($accounts);


        $balance = text(
            label: 'Enter Balance Amount',
            validate: fn ( $value) => !is_numeric($value) ? 'Enter Valid Amount' : null
        );

        foreach ($accounts as $index => $account){
            if ($account['account_number'] == $accountNumber){
                $accounts[$index]['balance'] =  (double) $accounts[$index]['balance'] + (double) $balance;
            }
        }

        file_put_contents($filePath, serialize($accounts));

        $this->info('Balance Deposited Successfully!');
        $this->goToMenu($menu);
    }

    public function withdrawAmount(CliMenu $menu)
    {
        list($filePath, $accounts) = $this->getAccounts();
        $accountNumber = $this->getAccount($accounts);

        $CurrentBalance = collect($accounts)->where('account_number', $accountNumber)->first()['balance'] ?? 0;

        $balance = text(
            label: 'Enter Balance Amount',
            validate: fn (string $value) => match (true) {
                !is_numeric($value) => 'Enter Valid Amount',
                (double)($value) > (double) $CurrentBalance => 'You do not have sufficient balance',
                default => null
            }

        );

        foreach ($accounts as $index => $account){
            if ($account['account_number'] == $accountNumber){
                $accounts[$index]['balance'] =  (double) $accounts[$index]['balance'] - (double) $balance;
            }
        }

        file_put_contents($filePath, serialize($accounts));

        $this->info('Balance Withdrawn Successfully!');
        $this->goToMenu($menu);
    }

    public function searchAccount(CliMenu $menu)
    {
        list($filePath, $accounts) = $this->getAccounts();
        $accountNumber = $this->getAccount($accounts);

        $searchAccount = collect($accounts)->where('account_number', $accountNumber)->all();
        $this->displayList($searchAccount, $menu);

    }
    protected function validateAccountNumber($value, $accounts)
    {
        return (bool)collect($accounts)->where('account_number', $value)->first();
    }

    /**
     * @param array $searchAccount
     * @param CliMenu $menu
     * @return void
     */
    protected function displayList(array $searchAccount, CliMenu $menu)
    {
        $this->table(['Name', 'A\C No', 'Account Type', 'Balance', 'Created At'], collect($searchAccount)->map(function ($account) {
            return [
                $account['name'] ?? 'N/A',
                $account['account_number'] ?? 'N/A',
                $account['account_type'] ?? 'N/A',
                $account['balance'] ?? 'N/A',
                $account['created_at'] ?? 'N/A',
            ];
        }));

        $this->goToMenu($menu);
    }


    protected function getAccounts()
    {
        $filePath = storage_path('accounts.txt');
        $file = 'accounts.txt';
        $accounts = [];
        if (Storage::exists($file) && Storage::size($file) > 0) {
            $accounts = unserialize(file_get_contents($filePath));
        }
        return array($filePath, $accounts);
    }


    protected function goToMenu(CliMenu $menu): void
    {
        $this->ask('enter any key to go home');

        if ($menu->isOpen()) {
            $menu->redraw();
        }
    }


    protected function getAccount(mixed $accounts): string
    {
        return text(
            label: 'Enter your Account Number',
            validate: fn($value) => !$this->validateAccountNumber($value, $accounts) ? 'No Account Exists! Enter your Valid Account' : null,
        );
    }


}
