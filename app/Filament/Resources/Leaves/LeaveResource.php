<?php

namespace App\Filament\Resources\Leaves;

use App\Filament\Resources\Leaves\Pages\CreateLeave;
use App\Filament\Resources\Leaves\Pages\EditLeave;
use App\Filament\Resources\Leaves\Pages\ListLeaves;
use App\Models\Leave;
use App\Models\Teacher;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeaveResource extends Resource
{
    protected static ?string $model = Leave::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 530;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('hr.view') ?? false;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Organization');
    }

    public static function getModelLabel(): string
    {
        return __('Leave');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Leaves');
    }

    public static function form(Form $form): Form
    {
        return $schema
            ->components([
                Select::make('teacher_id')
                    ->label(__('Teacher'))
                    ->relationship('teacher', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                    ->required()
                    ->preload()
                    ->searchable(),
                Select::make('leave_type_id')
                    ->label(__('Leave Type'))
                    ->relationship('leaveType', 'name')
                    ->required()
                    ->preload(),
                DatePicker::make('date')
                    ->label(__('Date'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('teacher.name')
                    ->label(__('Teacher'))
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->whereHas('teacher.user', function (Builder $q) use ($search) {
                            $q->where('firstname', 'like', "%{$search}%")
                                ->orWhere('lastname', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),
                TextColumn::make('leaveType.name')
                    ->label(__('Type'))
                    ->sortable(),
                TextColumn::make('date')
                    ->label(__('Date'))
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('teacher_id')
                    ->label(__('Teacher'))
                    ->options(fn () => Teacher::with('user')->get()->pluck('name', 'id'))
                    ->searchable(),
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')->label(__('From')),
                        DatePicker::make('until')->label(__('Until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $date) => $q->where('date', '>=', $date))
                            ->when($data['until'], fn (Builder $q, $date) => $q->where('date', '<=', $date));
                    }),
            ])
            ->actions([
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
            'index' => ListLeaves::route('/'),
            'create' => CreateLeave::route('/create'),
            'edit' => EditLeave::route('/{record}/edit'),
        ];
    }
}
