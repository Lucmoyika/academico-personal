<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Resources\Students\StudentResource;
use App\Models\Course;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;

class CourseEnrollments extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = CourseResource::class;

    protected string $view = 'filament.resources.courses.pages.course-enrollments';

    #[Url]
    public string $viewMode = 'list';

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public static function canAccess(array $parameters = []): bool
    {
        if (! isset($parameters['record'])) {
            return false;
        }

        $course = $parameters['record'] instanceof Course
            ? $parameters['record']
            : Course::find($parameters['record']);

        if (! $course) {
            return false;
        }

        return Gate::allows('view-course', $course);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getRecord()->enrollments()->getQuery())
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('student.user.lastname')
                    ->label(__('Last name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student.user.firstname')
                    ->label(__('First name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('enrollmentStatus.name')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Paid' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('total_price')
                    ->label(__('Price'))
                    ->money(config('academico.currency_code', 'USD'))
                    ->sortable(),
                TextColumn::make('student.user.email')
                    ->label(__('Email'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('scholarships.name')
                    ->label(__('Scholarships'))
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->recordUrl(fn ($record) => auth()->user()->can('enrollments.edit')
                ? EnrollmentResource::getUrl('edit', ['record' => $record])
                : StudentResource::getUrl('edit', ['record' => $record->student_id]))
            ->recordActions([
                Action::make('view_student')
                    ->label(__('Student'))
                    ->icon('heroicon-o-user')
                    ->url(fn ($record) => StudentResource::getUrl('edit', ['record' => $record->student_id]))
                    ->visible(fn () => auth()->user()->can('enrollments.edit')),
            ]);
    }

    public function getTitle(): string
    {
        return __('Enrollments') . ' — ' . $this->getRecord()->name;
    }

    public function getRosterEnrollments(): \Illuminate\Support\Collection
    {
        return $this->getRecord()
            ->enrollments()
            ->with('student.media', 'student.user')
            ->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('switch_to_roster')
                ->label(__('Photo roster'))
                ->icon('heroicon-o-photo')
                ->action(fn () => $this->viewMode = 'roster')
                ->visible(fn () => $this->viewMode === 'list'),
            Action::make('switch_to_list')
                ->label(__('List view'))
                ->icon('heroicon-o-list-bullet')
                ->action(fn () => $this->viewMode = 'list')
                ->visible(fn () => $this->viewMode === 'roster'),
        ];
    }
}
