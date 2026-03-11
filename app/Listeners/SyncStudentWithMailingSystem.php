<?php

namespace App\Listeners;

use App\Events\EnrollmentCreated;
use App\Interfaces\MailingSystemInterface;
use App\Traits\ReportsErrors;

class SyncStudentWithMailingSystem
{
    use ReportsErrors;

    public function __construct(public MailingSystemInterface $mailingSystem)
    {
        //
    }

    public function handle(EnrollmentCreated $event): void
    {
        if (! config('mailing-system.external_mailing_enabled')) {
            return;
        }

        try {
            $student = $event->enrollment->student;
            $user = $student->user;

            $listId = config('mailing-system.mailerlite.activeStudentsListId');

            if ($user->email && $user->firstname && $user->lastname && $listId) {
                $this->mailingSystem->subscribeUser($user->email, $user->firstname, $user->lastname, $listId);
            }
        } catch (\Throwable $e) {
            $this->reportError($e, 'SyncStudentWithMailingSystem::handle', [
                'enrollment_id' => $event->enrollment->id,
            ]);
        }
    }
}
