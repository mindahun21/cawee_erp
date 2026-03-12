<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings;
use App\Models\PrefixSetting;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class PrefixSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = Settings::class;

    protected string $view = 'filament.clusters.settings.pages.prefix-settings';

    protected static ?string $navigationLabel = 'Prefix Settings';

    protected static ?int $navigationSort = 4;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(
            PrefixSetting::all()->mapWithKeys(function ($setting) {
                return [
                    $setting->key . '_prefix' => $setting->prefix,
                    $setting->key . '_next_number' => $setting->next_number,
                ];
            })->toArray()
        );
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Inventory Settings')
                    ->columns(['default' => 2])
                    ->schema([
                        TextInput::make('inventory_sku_prefix')
                            ->label('Inventory SKU Prefix'),
                        TextInput::make('inventory_sku_next_number')
                            ->label('Next Inventory SKU Number')
                            ->numeric(),
                    ]),

                Section::make('Warehouse Settings')
                    ->columns(['default' => 2])
                    ->schema([
                        TextInput::make('warehouse_code_prefix')
                            ->label('Warehouse Code Prefix'),
                        TextInput::make('warehouse_code_next_number')
                            ->label('Next Warehouse Code Number')
                            ->numeric(),
                    ]),

                Section::make('Asset Identity Settings')
                    ->columns(['default' => 2])
                    ->schema([
                        Section::make('Serial Number')
                            ->columns(['default' => 2])
                            ->schema([
                                TextInput::make('asset_serial_number_prefix')
                                    ->label('Serial Number Prefix'),
                                TextInput::make('asset_serial_number_next_number')
                                    ->label('Next Serial Number')
                                    ->numeric(),
                            ]),
                        Section::make('Barcode')
                            ->columns(['default' => 2])
                            ->schema([
                                TextInput::make('asset_barcode_prefix')
                                    ->label('Barcode Prefix'),
                                TextInput::make('asset_barcode_next_number')
                                    ->label('Next Barcode Number')
                                    ->numeric(),
                            ]),
                        Section::make('QR Code')
                            ->columns(['default' => 2])
                            ->schema([
                                TextInput::make('asset_qr_code_prefix')
                                    ->label('QR Code Prefix'),
                                TextInput::make('asset_qr_code_next_number')
                                    ->label('Next QR Code Number')
                                    ->numeric(),
                            ]),
                        Section::make('RFID Tag')
                            ->columns(['default' => 2])
                            ->schema([
                                TextInput::make('asset_rfid_tag_prefix')
                                    ->label('RFID Tag Prefix'),
                                TextInput::make('asset_rfid_tag_next_number')
                                    ->label('Next RFID Tag Number')
                                    ->numeric(),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            if (str_ends_with($key, '_prefix')) {
                $settingKey = str_replace('_prefix', '', $key);
                PrefixSetting::where('key', $settingKey)->update(['prefix' => $value]);
            } elseif (str_ends_with($key, '_next_number')) {
                $settingKey = str_replace('_next_number', '', $key);
                PrefixSetting::where('key', $settingKey)->update(['next_number' => $value]);
            }
        }

        Notification::make()
            ->title('Prefix settings saved successfully.')
            ->success()
            ->send();
    }
}
