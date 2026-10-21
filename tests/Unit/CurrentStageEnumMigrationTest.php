<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class CurrentStageEnumMigrationTest extends TestCase
{
    public function test_mysql_up_statement_keeps_every_stage_used_by_the_application(): void
    {
        $connection = Mockery::mock();
        $connection->shouldReceive('getDriverName')
            ->once()
            ->andReturn('mysql');

        DB::shouldReceive('connection')
            ->once()
            ->andReturn($connection);
        DB::shouldReceive('statement')
            ->once()
            ->with("ALTER TABLE permohonans MODIFY current_stage ENUM('fo_input','fo_ttd_warga','kasi_pemerintahan_review','lurah_review','kasi_umum_review','sekcam_review','camat_review','fo_finalisasi','selesai') NOT NULL DEFAULT 'fo_input'");

        $migration = require base_path(
            'database/migrations/2026_10_15_183000_add_fo_ttd_warga_to_current_stage_enum.php'
        );

        $migration->up();
    }

    public function test_mysql_down_statement_preserves_fo_finalisasi_in_the_previous_enum(): void
    {
        $connection = Mockery::mock();
        $connection->shouldReceive('getDriverName')
            ->once()
            ->andReturn('mysql');

        $query = Mockery::mock();
        $query->shouldReceive('where')
            ->once()
            ->with('current_stage', 'fo_ttd_warga')
            ->andReturnSelf();
        $query->shouldReceive('exists')
            ->once()
            ->andReturnFalse();

        DB::shouldReceive('connection')
            ->once()
            ->andReturn($connection);
        DB::shouldReceive('table')
            ->once()
            ->with('permohonans')
            ->andReturn($query);
        DB::shouldReceive('statement')
            ->once()
            ->with("ALTER TABLE permohonans MODIFY current_stage ENUM('fo_input','kasi_pemerintahan_review','lurah_review','kasi_umum_review','sekcam_review','camat_review','fo_finalisasi','selesai') NOT NULL DEFAULT 'fo_input'");

        $migration = require base_path(
            'database/migrations/2026_10_15_183000_add_fo_ttd_warga_to_current_stage_enum.php'
        );

        $migration->down();
    }
}
