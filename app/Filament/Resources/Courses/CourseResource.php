<?php

namespace App\Filament\Resources\Courses;

use App\Filament\Resources\Courses\Pages\CourseBlockView;
use App\Filament\Resources\Courses\Pages\CourseEnrollments;
use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\Pages\ListCourses;
use App\Models\Course;
use App\Models\Period;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('Academic');
    }

    public static function getModelLabel(): string
    {
        return __('Course');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Courses');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Course')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Course Info')
                            ->schema([
                                Select::make('rhythm_id')
                                    ->relationship('rhythm', 'name')
                                    ->required()
                                    ->preload()
                                    ->searchable(),
                                Select::make('level_id')
                                    ->relationship('level', 'name')
                                    ->preload()
                                    ->searchable()
                                    ->nullable(),
                                TextInput::make('name')
                                    ->required()
                                    ->minLength(1)
                                    ->maxLength(100),
                                TextInput::make('price')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->prefix(config('academico.currency_position') === 'before' ? config('academico.currency_symbol') : null)
                                    ->suffix(config('academico.currency_position') === 'after' ? config('academico.currency_symbol') : null),
                                TextInput::make('price_b')
                                    ->label(__('Price B'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->prefix(config('academico.currency_position') === 'before' ? config('academico.currency_symbol') : null)
                                    ->suffix(config('academico.currency_position') === 'after' ? config('academico.currency_symbol') : null)
                                    ->visible(fn (): bool => (bool) config('invoicing.price_categories_enabled')),
                                TextInput::make('price_c')
                                    ->label(__('Price C'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->prefix(config('academico.currency_position') === 'before' ? config('academico.currency_symbol') : null)
                                    ->suffix(config('academico.currency_position') === 'after' ? config('academico.currency_symbol') : null)
                                    ->visible(fn (): bool => (bool) config('invoicing.price_categories_enabled')),
                                TextInput::make('volume')
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('h')
                                    ->nullable(),
                                TextInput::make('remote_volume')
                                    ->label(__('Remote volume'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('h')
                                    ->nullable(),
                                TextInput::make('spots')
                                    ->required()
                                    ->integer()
                                    ->minValue(0),
                                Checkbox::make('exempt_attendance')
                                    ->label(__('Exempt from attendance')),
                                ColorPicker::make('color')
                                    ->nullable(),
                            ]),

                        Tab::make('Resources')
                            ->schema([
                                Select::make('teacher_id')
                                    ->relationship('teacher', 'id')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                                Select::make('room_id')
                                    ->relationship('room', 'name')
                                    ->preload()
                                    ->nullable(),
                            ]),

                        Tab::make('Pedagogy')
                            ->schema([
                                Select::make('books')
                                    ->relationship('books', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->searchable(),
                                Select::make('evaluation_type_id')
                                    ->relationship('evaluationType', 'name')
                                    ->preload()
                                    ->nullable(),
                                Checkbox::make('marked')
                                    ->label(__('Evaluation ready'))
                                    ->visibleOn('edit'),
                            ]),

                        Tab::make('Schedule')
                            ->schema([
                                Select::make('period_id')
                                    ->relationship('period', 'name')
                                    ->default(fn (): ?int => Period::get_default_period()?->id)
                                    ->required()
                                    ->preload()
                                    ->searchable(),
                                DatePicker::make('start_date')
                                    ->required(),
                                DatePicker::make('end_date')
                                    ->required(),
                                Repeater::make('courseTimes')
                                    ->relationship('times')
                                    ->label(__('Recurring schedule'))
                                    ->schema([
                                        Select::make('day')
                                            ->options([
                                                0 => __('Sunday'),
                                                1 => __('Monday'),
                                                2 => __('Tuesday'),
                                                3 => __('Wednesday'),
                                                4 => __('Thursday'),
                                                5 => __('Friday'),
                                                6 => __('Saturday'),
                                            ])
                                            ->required(),
                                        TimePicker::make('start')
                                            ->required()
                                            ->seconds(false),
                                        TimePicker::make('end')
                                            ->required()
                                            ->seconds(false),
                                    ])
                                    ->columns(3)
                                    ->defaultItems(0)
                                    ->reorderable(false),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $defaultPeriod = Period::get_default_period();

        return $table
            ->columns([
                TextColumn::make('rhythm.name')
                    ->sortable(),
                TextColumn::make('level.name')
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('volume')
                    ->suffix('h')
                    ->sortable()
                    ->toggleable()
                ,
                TextColumn::make('teacher.name')
                    ->label(__('Teacher'))
                    ->sortable()
                    ->toggleable()
                ,
                TextColumn::make('room.name')
                    ->sortable()
                    ->toggleable()
                ,
                TextColumn::make('course_times')
                    ->label(__('Schedule'))
                    ->toggleable()
                ,
                TextColumn::make('course_enrollments_count')
                    ->label(__('Enrollments'))
                    ->sortable()
                    ->toggleable()
                ,
                TextColumn::make('start_date')
                    ->date()
                    ->sortable()
                    ->toggleable()
                ,
                TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->toggleable()
                ,
                IconColumn::make('marked')
                    ->boolean()
                    ->label(__('Eval'))
                    ->toggleable()
                ,
            ])
            ->filters([
                SelectFilter::make('period_id')
                    ->relationship('period', 'name')
                    ->label(__('Period'))
                    ->default($defaultPeriod?->id)
                    ->preload(),
                SelectFilter::make('rhythm_id')
                    ->relationship('rhythm', 'name')
                    ->label(__('Rhythm'))
                    ->preload(),
                SelectFilter::make('teacher_id')
                    ->relationship('teacher', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                    ->label(__('Teacher'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('level_id')
                    ->relationship('level', 'name')
                    ->label(__('Level'))
                    ->preload(),
            ])
            ->defaultSort('start_date', 'desc')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('view_enrollments')
                        ->label(__('View Enrollments'))
                        ->icon('heroicon-o-academic-cap')
                        ->url(fn ($record) => static::getUrl('enrollments', ['record' => $record])),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourses::route('/'),
            'block-view' => CourseBlockView::route('/block-view'),
            'create' => CreateCourse::route('/create'),
            'edit' => EditCourse::route('/{record}/edit'),
            'enrollments' => CourseEnrollments::route('/{record}/enrollments'),
        ];
    }
}
