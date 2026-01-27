<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotifyMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order_number, $notice, $link, $lang;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($order_number, $notice, $link, $lang)
    {
        $this->order_number = $order_number;
        $this->notice = $notice;
        $this->link = $link;
        $this->lang = strtolower($lang);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("Notice")
        ->from('invoice@damaro-slovakia.eu')
        ->view('emails.notify');
    }
}