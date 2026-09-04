<?php

namespace App\Livewire\Admin\Advance;

use App\Support\AdminActivity;
use App\Support\DatabaseDumper;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Database extends Component
{
    public string $connectionName = '';

    public int $tableCount = 0;

    public string $sizeMb = '0';

    public function mount(): void
    {
        $this->refreshStatus();
    }

    public function download()
    {
        $filename = 'database-'.now()->format('Y-m-d-His').'.sql';
        $path = storage_path('app/'.$filename);

        DatabaseDumper::dumpTo($path);

        AdminActivity::log('advance.database.download', 'Database downloaded');

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    protected function refreshStatus(): void
    {
        $this->connectionName = config('database.connections.mysql.database');

        $this->tableCount = count(DB::select('SHOW TABLES'));

        $size = DB::selectOne(
            'SELECT SUM(data_length + index_length) AS bytes FROM information_schema.TABLES WHERE table_schema = ?',
            [$this->connectionName]
        );

        $this->sizeMb = number_format(($size->bytes ?? 0) / 1024 / 1024, 2);
    }

    public function render()
    {
        return view('livewire.admin.advance.database')->layout('layouts.admin', ['title' => 'Database']);
    }
}
