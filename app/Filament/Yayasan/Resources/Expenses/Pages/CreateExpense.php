<?php

namespace App\Filament\Yayasan\Resources\Expenses\Pages;

use App\Filament\Yayasan\Resources\Expenses\ExpenseResource;
use App\Models\DisbursementRequest; 
use Carbon\Carbon; 
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;
    
    // Method mount() Anda sudah benar, tidak perlu diubah
    public function mount(): void
    {
        parent::mount(); 

        if (request()->has('disbursement_request_id')) {
            $requestId = (int) request()->get('disbursement_request_id');
            
            $disbursement = DisbursementRequest::with([
                'budgetItem.account', 
                'budgetItem.budget.department', 
                'budgetItem.budget' 
            ])->find($requestId);

            if ($disbursement && $disbursement->budgetItem && $disbursement->budgetItem->account) {
                
                $budget = $disbursement->budgetItem->budget; 

                $this->form->fill([
                    'disbursement_request_id' => $disbursement->id,
                    'school_id' => $budget->department->school_id,
                    'expense_account_id' => $disbursement->budgetItem->account_id,
                    'amount' => $disbursement->requested_amount, 
                    'description' => "(Otomatis) Realisasi untuk: " . $disbursement->budgetItem->description,
                    'date' => Carbon::today()->toDateString(),
                ]);

                if ($budget->cash_source_account_id) {
                    $this->form->fill([
                        'cash_account_id' => $budget->cash_source_account_id,
                    ]);
                }

            } else {
                $this->halt();
                Notification::make()
                    ->title('Gagal Memuat Data')
                    ->body('Pos anggaran terkait ajuan ini belum di-mapping ke Akun Beban di COA. Harap perbaiki di menu Budget.')
                    ->danger()
                    ->send();
            }
        }
    }

    protected function mutateDataBeforeCreate(array $data): array
    {
        $data['foundation_id'] = Filament::getTenant()->id;
        
        $data['created_by'] = auth()->id();

        if (auth()->user()->school_id) {
             $data['school_id'] = auth()->user()->school_id;
        } 
        elseif (empty($data['school_id']) && !empty($data['disbursement_request_id'])) {
            
            Log::warning('school_id kosong saat CreateExpense (Admin Yayasan). Mengambil ulang dari DisbursementRequest...');
            
            $disbursement = DisbursementRequest::with('budgetItem.budget.department')
                                ->find($data['disbursement_request_id']);
                                
            if ($disbursement) {
                $data['school_id'] = $disbursement->budgetItem->budget->department->school_id;
            } else {
                Log::error('Gagal menemukan DisbursementRequest saat mutateDataBeforeCreate.');
                $data['school_id'] = null; 
            }
        }
        return $data;
    }
}