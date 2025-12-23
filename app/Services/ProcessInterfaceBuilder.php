<?php
namespace App\Services;

class ProcessInterfaceBuilder
{
    public static function build($process)
    {
        $rows = [];

        foreach ($process->activities as $activity) {

            // Trouver la fonction responsable du process
            $actor = optional(
                $process->assignments
                    ->flatMap->functions
            )->firstWhere('function_id', $activity->id)?->function?->name;

            $rows[] = [
                'input'   => $process->inputs->pluck('label')->join(', '),
                'activity'=> $activity->name,
                'output'  => $process->outputs->pluck('label')->join(', '),
                'actor'   => $actor ?? '—',
                'document'=> null,
            ];
        }

        return $rows;
    }
}
