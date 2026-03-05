<?php

namespace App\Filament\Resources\OnboardingProcesses\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OnboardingProcessForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order')
                    ->label('Order')
                    ->numeric()
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('send_to_id')
                    ->label('Send To')
                    ->relationship('sendTo', 'name')
                    ->searchable()
                    ->required()
                    ->preload()
                    ->default(fn() => auth()->id()),

                TextInput::make('subject')
                    ->label('Subject')
                    ->required()
                    ->maxLength(255),

                RichEditor::make('content')
                    ->label('Content')
                    ->required()
                    ->placeholder('Write the onboarding step content here...')
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'link',
                        'bulletList',
                        'orderedList',
                        'blockquote',
                        'codeBlock'
                    ])
                    ->columnSpanFull(),

                FileUpload::make('attachment')
                    ->label('Attachment')
                    ->directory('onboarding_attachments')
                    ->nullable()
                    ->maxSize(10240)
                    ->acceptedFileTypes(['application/pdf', 'image/*']),
            ]);
    }
}
