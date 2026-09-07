<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Jenis dokumen hasil klasifikasi DocumentClassifier.
 *
 * Digunakan untuk menentukan parser mana yang akan digunakan
 * pada tahap selanjutnya (sprint berikutnya).
 */
enum DocumentType: string
{
    case Unknown = 'UNKNOWN';
    case TransferReceipt = 'TRANSFER_RECEIPT';
    case ShoppingReceipt = 'SHOPPING_RECEIPT';
    case QrisReceipt = 'QRIS_RECEIPT';
    case BankStatement = 'BANK_STATEMENT';
    case PaymentReceipt = 'PAYMENT_RECEIPT';
    case TopupReceipt = 'TOPUP_RECEIPT';
    case WithdrawReceipt = 'WITHDRAW_RECEIPT';
    case DepositReceipt = 'DEPOSIT_RECEIPT';
    case Invoice = 'INVOICE';
    case Other = 'OTHER';
    // P0 Fix: Add missing canonical types for LLM output aliases
    case BankReceipt = 'BANK_RECEIPT';
    case EWalletReceipt = 'E_WALLET_RECEIPT';
    case Bill = 'BILL';

    /**
     * Label untuk ditampilkan di UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Unknown => 'Unknown',
            self::TransferReceipt => 'Transfer Receipt',
            self::ShoppingReceipt => 'Shopping Receipt',
            self::QrisReceipt => 'QRIS Receipt',
            self::BankStatement => 'Bank Statement',
            self::PaymentReceipt => 'Payment Receipt',
            self::TopupReceipt => 'Top-up Receipt',
            self::WithdrawReceipt => 'Withdraw Receipt',
            self::DepositReceipt => 'Deposit Receipt',
            self::Invoice => 'Invoice',
            self::Other => 'Other',
            self::BankReceipt => 'Bank Receipt',
            self::EWalletReceipt => 'E-Wallet Receipt',
            self::Bill => 'Bill',
        };
    }

    /**
     * Warna badge untuk UI (Tailwind class suffix).
     */
    public function color(): string
    {
        return match ($this) {
            self::Unknown => 'gray',
            self::TransferReceipt => 'blue',
            self::ShoppingReceipt => 'orange',
            self::QrisReceipt => 'cyan',
            self::BankStatement => 'indigo',
            self::PaymentReceipt => 'green',
            self::TopupReceipt => 'emerald',
            self::WithdrawReceipt => 'red',
            self::DepositReceipt => 'teal',
            self::Invoice => 'violet',
            self::Other => 'slate',
            self::BankReceipt => 'indigo',
            self::EWalletReceipt => 'violet',
            self::Bill => 'amber',
        };
    }

    /**
     * Icon unicode untuk UI.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Unknown => '?',
            self::TransferReceipt => '💸',
            self::ShoppingReceipt => '🛒',
            self::QrisReceipt => '📱',
            self::BankStatement => '🏦',
            self::PaymentReceipt => '✅',
            self::TopupReceipt => '💰',
            self::WithdrawReceipt => '🏧',
            self::DepositReceipt => '📥',
            self::Invoice => '🧾',
            self::Other => '📄',
            self::BankReceipt => '🏦',
            self::EWalletReceipt => '📱',
            self::Bill => '🧾',
        };
    }

    /**
     * Normalize LLM alias to canonical DocumentType.
     * Handles BANK_RECEIPT family, aliases, canonical mapping.
     * Used for validation before DocumentType::tryFrom.
     *
     * @return self|null Returns canonical type or null if no mapping exists.
     */
    public static function normalize(string $raw): ?self
    {
        $upper = strtoupper(trim($raw));
        // Direct match first
        $direct = self::tryFrom($upper);
        if ($direct !== null) {
            return $direct;
        }

        // Alias normalization map: LLM semantic alias => canonical internal
        $aliasMap = [
            // Bank receipt family -> canonical
            'BANK_RECEIPT' => self::BankReceipt,
            'BANK_TRANSFER_RECEIPT' => self::TransferReceipt,
            'BANK_STATEMENT' => self::BankStatement,
            // Transfer receipt aliases
            'TRANSFER_RECEIPT' => self::TransferReceipt,
            // Deposit/withdrawal aliases
            'DEPOSIT_RECEIPT' => self::DepositReceipt,
            'WITHDRAWAL_RECEIPT' => self::WithdrawReceipt,
            'WITHDRAW_RECEIPT' => self::WithdrawReceipt,
            'TOPUP_RECEIPT' => self::TopupReceipt,
            'TOP_UP_RECEIPT' => self::TopupReceipt,
            // Shopping aliases
            'SHOPPING_RECEIPT' => self::ShoppingReceipt,
            'RETAIL_RECEIPT' => self::ShoppingReceipt,
            'PURCHASE_RECEIPT' => self::ShoppingReceipt,
            // QRIS aliases
            'QRIS_RECEIPT' => self::QrisReceipt,
            'QRIS_PAYMENT_RECEIPT' => self::QrisReceipt,
            'E_WALLET_RECEIPT' => self::EWalletReceipt,
            'EWALLET_RECEIPT' => self::EWalletReceipt,
            // Invoice/Bill aliases
            'INVOICE' => self::Invoice,
            'BILL' => self::Bill,
            'UNKNOWN' => self::Unknown,
            'OTHER' => self::Other,
        ];

        return $aliasMap[$upper] ?? null;
    }
}
