<?php

namespace App\Mail;

use App\Models\PayrollRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** FSD 13.7 — "Email, when enabled." */
class PayslipMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PayrollRecord $payroll, public string $pdfContent, public string $fileName)
    {
    }

    public function envelope(): Envelope
    {
        $period = \Carbon\Carbon::create($this->payroll->year, $this->payroll->month, 1)->format('F Y');
        return new Envelope(subject: "Payslip for {$period}");
    }

    public function content(): Content
    {
        return new Content(view: 'payroll.payslip-email');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, $this->fileName)->withMime('application/pdf'),
        ];
    }
}
