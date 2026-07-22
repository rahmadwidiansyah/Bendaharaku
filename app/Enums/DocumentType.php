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
        };
    }
}
