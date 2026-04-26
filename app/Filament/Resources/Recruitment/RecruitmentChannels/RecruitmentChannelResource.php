<?php

namespace App\Filament\Resources\Recruitment\RecruitmentChannels;

use App\Filament\Components\FormSchemaBuilder;
use App\Filament\Resources\Recruitment\RecruitmentChannels\Pages;
use App\Models\Recruitment\RecruitmentChannel;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Traits\BelongsToModule;

class RecruitmentChannelResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = RecruitmentChannel::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-globe-alt';
    protected static \UnitEnum|string|null $navigationGroup = 'Recruitment';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Channels';
    protected static ?string $modelLabel = 'Channel';
    protected static ?string $pluralModelLabel = 'Channels';

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Tabs::make('Tabs')
                ->columnSpanFull()
                ->tabs([
                    \Filament\Schemas\Components\Tabs\Tab::make('Form information')
                        ->schema([
                            \Filament\Schemas\Components\Grid::make(2)->schema([
                                // LEFT COLUMN
                                \Filament\Schemas\Components\Group::make()->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Form Name')
                                        ->required(),

                                    Forms\Components\Select::make('type')
                                        ->label('Form type')
                                        ->options([
                                            'online'   => 'Online',
                                            'offline'  => 'Offline',
                                            'agency'   => 'Agency',
                                            'internal' => 'Internal',
                                        ])
                                        ->required(),

                                    Forms\Components\Select::make('language')
                                        ->label('Language')
                                        ->options(['en' => 'English', 'am' => 'Amharic'])
                                        ->default('en')
                                        ->required(),

                                    Forms\Components\TextInput::make('submit_button_text')
                                        ->label('Submit button text')
                                        ->default('Submit')
                                        ->required(),

                                    Forms\Components\Textarea::make('success_message')
                                        ->label('Message to show after form is successfully submitted')
                                        ->rows(3)
                                        ->required(),
                                ]),

                                // RIGHT COLUMN
                                \Filament\Schemas\Components\Group::make()->schema([
                                    Forms\Components\Select::make('status')
                                        ->label('Status')
                                        ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                                        ->required(),

                                    Forms\Components\Select::make('responsible_person_id')
                                        ->label('Responsible person')
                                        ->relationship('responsiblePerson', 'name')
                                        ->nullable(),

                                    Forms\Components\Placeholder::make('notification_settings_label')
                                        ->label('')
                                        ->content(new \Illuminate\Support\HtmlString('<strong>Notification settings</strong>')),

                                    Forms\Components\Toggle::make('notify_on_submission')
                                        ->label('Notify when new candidates')
                                        ->default(true)
                                        ->live(),

                                    Forms\Components\Radio::make('notification_target')
                                        ->label('')
                                        ->options([
                                            'specific_staff'    => 'Specific Staff Members',
                                            'staff_with_roles'  => 'Staff members with roles',
                                            'responsible_person'=> 'Responsible person',
                                        ])
                                        ->default('specific_staff')
                                        ->inline()
                                        ->visible(fn (Get $get) => $get('notify_on_submission')),

                                    Forms\Components\Select::make('notification_person_id')
                                        ->label('Person in charge')
                                        ->relationship('notificationPerson', 'name')
                                        ->placeholder('Not required')
                                        ->nullable()
                                        ->visible(fn (Get $get) =>
                                            $get('notify_on_submission') &&
                                            $get('notification_target') === 'specific_staff'
                                        ),
                                ]),
                            ]),
                        ]),

                    \Filament\Schemas\Components\Tabs\Tab::make('Form builder')
                        ->extraAttributes(['class' => 'channel-form-builder'])
                        ->schema([
                            FormSchemaBuilder::make('form_schema')
                                ->label('')
                                ->columnSpanFull(),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Form name')->searchable(),
                Tables\Columns\TextColumn::make('responsiblePerson.name')->label('Person in charge'),
                Tables\Columns\TextColumn::make('type')->label('Form type'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors(['success' => 'active', 'danger' => 'inactive']),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type'),
            ])
            ->actions([
                Action::make('previewForm')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading('Form Preview')
                    ->modalContent(fn ($record) => view('components.recruitment.channel-form-preview', ['schema' => $record->form_schema]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                ViewAction::make(),
                EditAction::make(),
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
            'index' => Pages\ListRecruitmentChannels::route('/'),
            'create' => Pages\CreateRecruitmentChannel::route('/create'),
            'view' => Pages\ViewRecruitmentChannel::route('/{record}'),
            'edit' => Pages\EditRecruitmentChannel::route('/{record}/edit'),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Tabs::make('ChannelDetailsTabs')
                ->columnSpanFull()
                ->tabs([
                    \Filament\Schemas\Components\Tabs\Tab::make('Form Information')
                        ->schema([
                            \Filament\Schemas\Components\Grid::make(2)
                                ->schema([
                                    \Filament\Schemas\Components\Group::make([
                                        TextEntry::make('name')
                                            ->label('Form Name'),
                                        TextEntry::make('type')
                                            ->label('Form type')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),
                                        TextEntry::make('language')
                                            ->label('Language')
                                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                                'en' => 'English',
                                                'am' => 'Amharic',
                                                default => (string) $state,
                                            }),
                                        TextEntry::make('submit_button_text')
                                            ->label('Submit button text'),
                                        TextEntry::make('success_message')
                                            ->label('Message to show after form is successfully submitted')
                                            ->columnSpanFull(),
                                    ]),
                                    \Filament\Schemas\Components\Group::make([
                                        TextEntry::make('status')
                                            ->label('Status')
                                            ->badge()
                                            ->color(fn (?string $state): string => match ($state) {
                                                'active' => 'success',
                                                'inactive' => 'danger',
                                                default => 'gray',
                                            }),
                                        TextEntry::make('responsiblePerson.name')
                                            ->label('Responsible person')
                                            ->default('—'),
                                        IconEntry::make('notify_on_submission')
                                            ->label('Notify when new candidates')
                                            ->boolean(),
                                        TextEntry::make('notification_target')
                                            ->label('Notification target')
                                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                                'specific_staff' => 'Specific Staff Members',
                                                'staff_with_roles' => 'Staff members with roles',
                                                'responsible_person' => 'Responsible person',
                                                default => $state ?: '—',
                                            })
                                            ->visible(fn (RecruitmentChannel $record): bool => (bool) $record->notify_on_submission),
                                        TextEntry::make('notificationPerson.name')
                                            ->label('Person in charge')
                                            ->default('—')
                                            ->visible(fn (RecruitmentChannel $record): bool => (bool) $record->notify_on_submission && $record->notification_target === 'specific_staff'),
                                    ]),
                                ]),
                        ]),
                    \Filament\Schemas\Components\Tabs\Tab::make('Form Preview')
                        ->schema([
                            ViewEntry::make('form_schema')
                                ->hiddenLabel()
                                ->view('components.recruitment.channel-form-preview')
                                ->viewData([
                                    'schema' => fn (RecruitmentChannel $record): array => $record->form_schema ?? [],
                                ])
                                ->columnSpanFull(),
                        ]),
                ]),
        ]);
    }
}
