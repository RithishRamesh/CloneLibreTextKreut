<?php


namespace App\Traits;


use Illuminate\Support\Facades\DB;

trait Statistics
{
    function getMeanAndStdDev(string $table, string $whereInCol, array $whereInValue, string $groupBy)
    {
        $statistics_by_groupBy = [];
        $statistics = DB::table($table)
            ->whereIn($whereInCol, $whereInValue)
            ->select($groupBy, DB::raw('AVG(score) as average'), DB::raw('STDDEV(score) as std_dev'))
            ->groupBy($groupBy)
            ->get();
        foreach ($statistics as $key => $value) {
            $statistics_by_groupBy[$value->{$groupBy}] = [
                'average' => $value->average,
                'std_dev' => $value->std_dev];
        }
        return $statistics_by_groupBy;

    }


    public function computeZScore($score, array $mean_and_std_dev)
    {
        if (in_array($score,[null,'N/A'])){
            return 'N/A';
        }
        if ($mean_and_std_dev['std_dev'] > 0) {
            $z_score = ($score - $mean_and_std_dev['average']) / $mean_and_std_dev['std_dev'];
            $z_score = round($z_score, 2);
        } else {
            $z_score = "Std Dev is 0";
        }
        return $z_score;
    }

    /**
     * Used for testing purposes
     * @param array $a
     * @param false $sample
     * @return false|float
     */
    function stats_standard_deviation(array $a, $sample = false)
    {
        $n = count($a);
        if ($n === 0) {
            trigger_error("The array has zero elements", E_USER_WARNING);
            return false;
        }
        if ($sample && $n === 1) {
            trigger_error("The array has only 1 element", E_USER_WARNING);
            return false;
        }
        $mean = array_sum($a) / $n;
        $carry = 0.0;
        foreach ($a as $val) {
            $d = ((double)$val) - $mean;
            $carry += $d * $d;
        };
        if ($sample) {
            --$n;
        }
        return sqrt($carry / $n);
    }

}