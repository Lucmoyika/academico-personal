<?php

namespace App\Listeners;

use App\Events\EnrollmentCreated;
use App\Interfaces\MailingSystemInterface;

class SyncStudentWithMailingSystem
{
    public function __construct(public MailingSystemInterface $mailingSystem)
    {
        //
    }

    public function handle(EnrollmentCreated $event): void
    {
        if (! config('mailing-system.external_mailing_enabled')) {
            return;
        }

        $student = $event->enrollment->student;
        $user = $student->user;

        $listId = config('mailing-system.mailerlite.activeStudentsListId');

        if ($user->email && $user->firstname && $user->lastname && $listId) {
            $this->mailingSystem->subscribeUser($user->email, $user->firstname, $user->lastname, $listId);
        }
    }
}
