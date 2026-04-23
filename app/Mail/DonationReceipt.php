<?php

namespace App\Mail;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;

use Illuminate\Contracts\Queue\ShouldQueue;

class DonationReceipt extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Donation $donation,
        public array $receiptData
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Donation Receipt - ' . $this->donation->receipt_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.donation-receipt',
            with: [
                'receiptNumber' => $this->donation->receipt_number,
                'donorName' => $this->receiptData['donor_name'],
                'amount' => $this->receiptData['amount'],
                'currency' => $this->receiptData['currency_symbol'],
                'donationType' => $this->receiptData['donation_type'],
                'campaign' => $this->receiptData['campaign'],
                'donationDate' => $this->receiptData['donation_date'],
                'isTaxDeductible' => $this->receiptData['is_tax_deductible'],
                'dateIssued' => $this->receiptData['date_issued'],
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
