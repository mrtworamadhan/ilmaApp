<?php

namespace App\Filament\Yayasan\Resources\Announcements\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Judul Pengumuman')
                    ->required()
                    ->columnSpanFull(),

                // Hanya Admin Yayasan yang bisa memilih sekolah
                // Admin Sekolah otomatis terisi school_id nya
                Select::make('school_id')
                    ->label('Target Sekolah')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('Kosongkan jika pengumuman ini untuk semua sekolah di yayasan.')
                    ->visible(fn() => auth()->user()->school_id === null), // Hanya untuk Admin Yayasan

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft (Simpan, belum publish)',
                        'published' => 'Published (Tampilkan)',
                    ])
                    ->required()
                    ->default('draft'),

                DateTimePicker::make('published_at')
                    ->label('Waktu Publish')
                    ->helperText('Atur waktu jika ingin publish otomatis di masa depan.'),

                TagsInput::make('target_roles')
                    ->label('Target Penerima (Opsional)')
                    ->helperText('Untuk pengembangan app-wali. Tekan enter setelah mengetik.')
                    ->placeholder('Contoh: Wali Murid, Guru...'),

                RichEditor::make('content')
                    ->label('Isi Pengumuman')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
