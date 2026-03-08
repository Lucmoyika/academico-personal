<?php

namespace App\Filament\Resources\Enrollments\Pages;

use App\Filament\Pages\GradeEdit;
use App\Filament\Pages\SkillEvaluationPage;
use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Resources\Enrollments\RelationManagers\EnrollmentCommentsRelationManager;
use App\Filament\Resources\Enrollments\RelationManagers\ScholarshipsRelationManager;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;

class ViewEnrollment extends ViewRecord
{
    protected static string $resource = EnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit_price')
                ->label(__('Price'))
                ->icon('heroicon-o-currency-dollar')
                ->fillForm(fn () => ['total_price' => $this->record->total_price])
                ->form([
                    TextInput::make('total_price')
                        ->label(__('Price'))
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->step(0.01)
                        ->prefix(config('academico.currency_position') === 'before' ? config('academico.currency_symbol') : null)
                        ->suffix(config('academico.currency_position') === 'after' ? config('academico.currency_symbol') : null),
                ])
                ->action(function (array $data) {
                    $this->record->update(['total_price' => $data['total_price']]);
                    $this->refreshFormData(['total_price']);
                }),

            Action::make('edit_status')
                ->label(__('Status'))
                ->icon('heroicon-o-arrow-path')
                ->fillForm(fn () => ['status_id' => $this->record->status_id])
                ->form([
                    Select::make('status_id')
                        ->label(__('Status'))
                        ->relationship('enrollmentStatus', 'name')
                        ->required()
                        ->preload(),
                ])
                ->action(function (array $data) {
                    $this->record->update(['status_id' => $data['status_id']]);
                    $this->refreshFormData(['status_id']);
                }),

            Action::make('change_course')
                ->label(__('Change course'))
                ->icon('heroicon-o-academic-cap')
                ->url(EnrollmentResource::getUrl('change-course', ['record' => $this->record])),

            Action::make('cancel')
                ->label(__('Cancel Enrollment'))
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->modalDescription('This will cancel the enrollment and delete associated attendance records. This action cannot be undone.')
                ->action(function () {
                    $this->record->cancel();
                    $this->redirect(EnrollmentResource::getUrl('index'));
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        $ownerRecord = $this->getRecord();
        $livewireData = ['ownerRecord' => $ownerRecord, 'pageClass' => static::class];

        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 2])
                    ->schema([
                        $this->getInfolistContentComponent()
                            ->columnSpan(1),
                        Grid::make(1)
                            ->schema([
                                Tabs::make()
                                    ->tabs([
                                        Tab::make(__('Result & Evaluation'))
                                            ->icon('heroicon-o-trophy')
                                            ->schema([
                                                Actions::make([
                                                    Action::make('edit_grades')
                                                        ->label(__('Manage Grades'))
                                                        ->icon('heroicon-o-pencil-square')
                                                        ->size('sm')
                                                        ->url(GradeEdit::getUrl(['courseId' => $ownerRecord->course_id]))
                                                        ->visible(auth()->user()?->hasRole('admin') && $ownerRecord->course?->evaluationType?->gradeTypes()?->count() > 0),
                                                    Action::make('edit_skills')
                                                        ->label(__('Evaluate Skills'))
                                                        ->icon('heroicon-o-star')
                                                        ->size('sm')
                                                        ->url(SkillEvaluationPage::getUrl(['courseId' => $ownerRecord->course_id]))
                                                        ->visible(auth()->user()?->hasRole('admin') && $ownerRecord->course?->evaluationType?->skills()?->count() > 0),
                                                ]),
                                                TextEntry::make('result.result_name.name')
                                                    ->label(__('Result'))
                                                    ->badge()
                                                    ->color($ownerRecord->result?->result_name?->color ? Color::hex($ownerRecord->result->result_name->color) : null)
                                                    ->state($ownerRecord->result?->result_name?->name)
                                                    ->placeholder('-'),
                                                TextEntry::make('total_grade')
                                                    ->label(__('Total'))
                                                    ->state($ownerRecord->grades->isEmpty() ? null : (string) $ownerRecord->grades->sum('grade'))
                                                    ->placeholder('-')
                                                    ->visible($ownerRecord->course?->evaluationType?->gradeTypes()?->count() > 0),
                                                TextEntry::make('result_comments')
                                                    ->label(__('Comments'))
                                                    ->state($ownerRecord->result?->comments?->pluck('body')->implode("\n"))
                                                    ->placeholder('-'),
                                            ]),
                                        Tab::make(__('Comments'))
                                            ->icon('heroicon-o-chat-bubble-left-right')
                                            ->schema([
                                                Livewire::make(EnrollmentCommentsRelationManager::class, [
                                                    ...$livewireData,
                                                    ...EnrollmentCommentsRelationManager::getDefaultProperties(),
                                                ])->key('comments'),
                                            ]),
                                    ]),
                                Tabs::make()
                                    ->tabs([
                                        Tab::make(__('Books'))
                                            ->icon('heroicon-o-book-open')
                                            ->schema([
                                                TextEntry::make('books')
                                                    ->label(__('Books'))
                                                    ->badge()
                                                    ->state($ownerRecord->course?->books?->pluck('name')->toArray() ?? [])
                                                    ->placeholder(__('No books assigned')),
                                            ]),
                                        Tab::make(__('Scholarships'))
                                            ->icon('heroicon-o-academic-cap')
                                            ->schema([
                                                Livewire::make(ScholarshipsRelationManager::class, [
                                                    ...$livewireData,
                                                    ...ScholarshipsRelationManager::getDefaultProperties(),
                                                ])->key('scholarships'),
                                            ]),
                                    ]),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }
}
