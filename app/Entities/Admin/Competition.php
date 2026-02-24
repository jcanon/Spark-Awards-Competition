<?php

namespace App\Entities\Admin;

use CodeIgniter\Entity\Entity;

class Competition extends Entity
{
    protected $dates = [
        'comp_phase_1_open',
        'comp_regular_reg_open',
        'comp_late_reg_open',
        'comp_phase_1_close',
        'jury_phase_1_open',
        'jury_phase_1_close',
        'comp_phase_2_open',
        'comp_phase_2_close',
        'jury_phase_2_open',
        'jury_phase_2_close',
    ];

    protected $casts = [
        'comp_id' => 'integer',
        'comp_type_id' => 'integer',
        'comp_year' => 'integer',
        'shortlist_enabled' => 'integer',
        'pro_early_reg_price' => 'float',
        'pro_regular_reg_price' => 'float',
        'pro_late_reg_price' => 'float',
        'pro_finalist_price' => 'float',
        'pro_winner_price' => 'float',
        'pro_series_price' => 'float',
        'student_early_reg_price' => 'float',
        'student_regular_reg_price' => 'float',
        'student_late_reg_price' => 'float',
        'student_finalist_price' => 'float',
        'student_winner_price' => 'float',
        'student_series_price' => 'float',
        'trophy_price' => 'float',
        'additional_trophy_price' => 'float',
    ];

    // Normalize incoming price strings like "$100.00" -> 100.00
    protected function sanitizeMoney($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (float)preg_replace('/[^0-9.\-]/', '', (string)$value);
    }

    // Mutators for price fields
    public function setProEarlyRegPrice($value)
    {
        $this->attributes['pro_early_reg_price'] = $this->sanitizeMoney($value);
        return $this;
    }

    public function setProRegularRegPrice($value)
    {
        $this->attributes['pro_regular_reg_price'] = $this->sanitizeMoney($value);
        return $this;
    }

    public function setProLateRegPrice($value)
    {
        $this->attributes['pro_late_reg_price'] = $this->sanitizeMoney($value);
        return $this;
    }

    public function setProFinalistPrice($value)
    {
        $this->attributes['pro_finalist_price'] = $this->sanitizeMoney($value);
        return $this;
    }

    public function setProWinnerPrice($value)
    {
        $this->attributes['pro_winner_price'] = $this->sanitizeMoney($value);
        return $this;
    }

    public function setProSeriesPrice($value)
    {
        $this->attributes['pro_series_price'] = $this->sanitizeMoney($value);
        return $this;
    }

    public function setStudentEarlyRegPrice($value)
    {
        $this->attributes['student_early_reg_price'] = $this->sanitizeMoney($value);
        return $this;
    }

    public function setStudentRegularRegPrice($value)
    {
        $this->attributes['student_regular_reg_price'] = $this->sanitizeMoney($value);
        return $this;
    }

    public function setStudentLateRegPrice($value)
    {
        $this->attributes['student_late_reg_price'] = $this->sanitizeMoney($value);
        return $this;
    }

    public function setStudentFinalistPrice($value)
    {
        $this->attributes['student_finalist_price'] = $this->sanitizeMoney($value);
        return $this;
    }

    public function setStudentWinnerPrice($value)
    {
        $this->attributes['student_winner_price'] = $this->sanitizeMoney($value);
        return $this;
    }

    public function setStudentSeriesPrice($value)
    {
        $this->attributes['student_series_price'] = $this->sanitizeMoney($value);
        return $this;
    }

    public function setTrophyPrice($value)
    {
        $this->attributes['trophy_price'] = $this->sanitizeMoney($value);
        return $this;
    }

    public function setAdditionalTrophyPrice($value)
    {
        $this->attributes['additional_trophy_price'] = $this->sanitizeMoney($value);
        return $this;
    }

    // Helper to present shortlist as boolean in views
    public function getShortlistEnabledBool(): bool
    {
        return (int)($this->attributes['shortlist_enabled'] ?? 0) === 1;
    }
}
