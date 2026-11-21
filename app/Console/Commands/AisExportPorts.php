<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Menulis daftar pelabuhan aktif ke storage/app/ais-ports-config.json untuk dibaca scripts/ais-listener.js */
class AisExportPorts extends Command
{
    protected $signature = 'ais:ports';
    protected $description = 'Export pelabuhan aktif (id, kode, koordinat) untuk listener AIS';

    public function handle(): int
    {
        $ports = DB::table('locations')->where('is_active', 1)->where('type', 'PORT')
            ->get(['id', 'code', 'name', 'latitude', 'longitude'])
            ->map(fn ($l) => ['id' => $l->id, 'code' => $l->code, 'name' => $l->name, 'lat' => (float) $l->latitude, 'lng' => (float) $l->longitude])->values();
        file_put_contents(storage_path('app/ais-ports-config.json'), $ports->toJson(JSON_PRETTY_PRINT));
        $this->info($ports->count().' pelabuhan ditulis ke storage/app/ais-ports-config.json');
        return self::SUCCESS;
    }
}
