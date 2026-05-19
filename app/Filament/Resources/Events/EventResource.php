<?php

namespace App\Filament\Resources\Events;

use App\Filament\Resources\Events\Pages\ListEvents;
use App\Models\Event;
use App\Models\Teacher;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?int $navigationSort = 430;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('courses.view') ?? false;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Organization');
    }

    public static function getModelLabel(): string
    {
        return __('Event');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Events');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->minLength(1)
                    ->maxLength(255),

                Select::make('course_id')
                    ->label(__('Course'))
                    ->relationship('course', 'name')
                    ->preload()
                    ->searchable()
                    ->nullable(),

                // 🔥 FIX TEACHER (USER RELATION SAFE)
                Select::make('teacher_id')
                    ->label(__('Teacher'))
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search) {
                        return Teacher::with('user')
                            ->whereHas('user', function ($q) use ($search) {
                                $q->where('firstname', 'like', "%{$search}%")
                                    ->orWhere('lastname', 'like', "%{$search}%");
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($teacher) {
                                return [
                                    $teacher->id => $teacher->name,
                                ];
                            });
                    })
                    ->getOptionLabelFromRecordUsing(function ($record) {
                        return $record?->name ?? null;
                    })
                    ->nullable(),

                Select::make('room_id')
                    ->label(__('Room'))
                    ->relationship('room', 'name')
                    ->preload()
                    ->nullable(),

                DateTimePicker::make('start')
                    ->label(__('Start'))
                    ->required(),

                DateTimePicker::make('end')
                    ->label(__('End'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mobile_event')
                    ->label(__('Event'))
                    ->state(fn ($record) => $record->name)
                    ->description(fn ($record) => collect([
                        $record->teacher?->name,
                        $record->room?->name,
                    ])->filter()->implode(' · ')
                    )
                    ->searchable(query: fn ($query, $search) => $query->where('name', 'like', "%{$search}%")
                    )
                    ->wrap()
                    ->hiddenFrom('md'),

                TextColumn::make('mobile_schedule')
                    ->label(__('Schedule'))
                    ->state(fn ($record) => $record->start?->format('M j, Y H:i'))
                    ->description(fn ($record) => $record->end?->format('M j, Y H:i'))
                    ->hiddenFrom('md'),

                TextColumn::make('name')
                    ->label(__('Name'))
                    ->wrap()
                    ->width('180px')
                    ->searchable()
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('course.name')
                    ->label(__('Course'))
                    ->wrap()
                    ->width('180px')
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('volume')
                    ->label(__('Hours'))
                    ->formatStateUsing(fn ($state): string => number_format($state, 1).'h')
                    ->sortable()
                    ->visibleFrom('md'),

                // 🔥 FIX TEACHER DISPLAY
                TextColumn::make('teacher')
                    ->label(__('Teacher'))
                    ->formatStateUsing(fn ($record) => $record->teacher?->name ?? '—')
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('room.name')
                    ->label(__('Room'))
                    ->sortable()
                    ->visibleFrom('lg'),

                TextColumn::make('start')
                    ->label(__('Start'))
                    ->dateTime()
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('end')
                    ->label(__('End'))
                    ->dateTime()
                    ->sortable()
                    ->visibleFrom('lg'),
            ])
            ->defaultSort('start', 'desc')
            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')->label(__('From')),
                        DatePicker::make('until')->label(__('Until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->where('start', '>=', $date)
                            )
                            ->when($data['until'], fn ($q, $date) => $q->where('start', '<=', $date.' 23:59:59')
                            );
                    }),

                TernaryFilter::make('orphan')
                    ->label(__('No course'))
                    ->queries(
                        true: fn ($query) => $query->whereNull('course_id'),
                        false: fn ($query) => $query->whereNotNull('course_id'),
                    ),

                TernaryFilter::make('unassigned')
                    ->label(__('No teacher'))
                    ->queries(
                        true: fn ($query) => $query->unassigned(),
                        false: fn ($query) => $query->whereNotNull('teacher_id'),
                    ),

                // 🔥 FIX FILTER
                SelectFilter::make('teacher_id')
                    ->label(__('Teacher'))
                    ->searchable()
                    ->options(
                        Teacher::with('user')
                            ->get()
                            ->mapWithKeys(fn ($teacher) => [
                                $teacher->id => $teacher->name,
                            ])
                    ),
            ])
            ->actions([
                EditAction::make(),
                ActionGroup::make([
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
        ];
    }
}
