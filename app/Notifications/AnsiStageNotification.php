<?php

namespace App\Notifications;

use App\Models\AnsiApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * ອີເມວ ແຈ້ງ ຄວາມ ຄືບໜ້າ ຂອງ ໃບ ANSI (Application for New Stock Item). ໃຊ້ ຮ່ວມ
 * ກັນ ໃຫ້ ຜູ້ ຕໍ່ ໄປ ທີ່ ຕ້ອງ ດຳເນີນ ການ (actionNeeded) ຫຼື ຜູ້ ຮ້ອງ ຂໍ (ອັບເດດ
 * ສະຖານະ). ສົ່ງ ແບບ best-effort — ຖ້າ SMTP ບໍ່ ຕັ້ງ, ບໍ່ ຂັດ flow (ລິ້ງ ໃນ ແອັບ
 * ຍັງ ໃຊ້ ໄດ້). ຄື ຮູບແບບ DisposalEndorsementRequest.
 */
class AnsiStageNotification extends Notification
{
    use Queueable;

    public function __construct(
        public AnsiApplication $app,
        public string $headline,
        public bool $actionNeeded = false,
    ) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('ansi.show', $this->app);
        $summary = Str::limit($this->app->summary_items ?: '—', 120);

        $mail = (new MailMessage)
            ->subject("ANSI {$this->app->request_number} · {$this->headline}")
            ->greeting('ສະບາຍດີ '.($notifiable->display_name ?: $notifiable->email))
            ->line("ໃບ ຂໍ ສ້າງ ລາຍການ ໃໝ່ (ANSI) ເລກທີ **{$this->app->request_number}** — {$this->headline}.")
            ->line("ລາຍການ: {$summary}");

        if ($this->actionNeeded) {
            $mail->line('ກະລຸນາ ກົດ ລິ້ງ ລຸ່ມ ນີ້ ເພື່ອ ກວດ ແລະ ດຳເນີນ ການ ໃນ ຂັ້ນ ຕອນ ຂອງ ທ່ານ.');
        }

        return $mail
            ->action('ເປີດ ໃບ ANSI / Open', $url)
            ->line('ຂອບໃຈ.');
    }
}
