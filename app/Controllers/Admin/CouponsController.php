<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class CouponsController extends BaseController
{
    public function index()
    {
        $competitionLabels = [0 => 'Global Coupon'];
        foreach (service('competitions')->getAllCompetitions() as $comp) {
            $compId = (int)($comp->comp_id ?? 0);
            if ($compId <= 0) {
                continue;
            }

            $competitionLabels[$compId] = trim((string)($comp->comp_type_name ?? '') . ' ' . (string)($comp->comp_year ?? ''));
        }

        return view('admin/coupons/index', [
            'rows' => service('coupons')->getAllCoupons(),
            'competitionLabels' => $competitionLabels,
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function create()
    {
        return view('admin/coupons/form', [
            'row' => null,
            'competitions' => service('competitions')->getAllCompetitions(),
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function store()
    {
        $rules = [
            'coupon_code' => 'required|max_length[25]',
            'coupon_type' => 'required|in_list[Dollar,Percentage]',
            'coupon_amount' => 'required|regex_match[/^[0-9]+$/]|greater_than[0]',
            'coupon_start_date' => 'required',
            'coupon_end_date' => 'required',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Invalid coupon data.');
        }

        service('coupons')->createCoupon($this->request->getPost());
        return redirect()->to('/admin/coupons')->with('success', 'Coupon created.');
    }

    public function edit(int $couponId)
    {
        $row = service('coupons')->getCouponByID($couponId);
        if (!$row) {
            return redirect()->to('/admin/coupons')->with('error', 'Coupon not found.');
        }

        return view('admin/coupons/form', [
            'row' => $row,
            'competitions' => service('competitions')->getAllCompetitions(),
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function update(int $couponId)
    {
        $rules = [
            'coupon_code' => 'required|max_length[25]',
            'coupon_type' => 'required|in_list[Dollar,Percentage]',
            'coupon_amount' => 'required|regex_match[/^[0-9]+$/]|greater_than[0]',
            'coupon_start_date' => 'required',
            'coupon_end_date' => 'required',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Invalid coupon data.');
        }

        if (!service('coupons')->updateCoupon($couponId, $this->request->getPost())) {
            return redirect()->back()->withInput()->with('error', 'Coupon update failed.');
        }

        return redirect()->to('/admin/coupons/edit/' . $couponId)->with('success', 'Coupon updated.');
    }

    public function delete(int $couponId)
    {
        if (!$this->canDelete()) {
            return redirect()->to('/admin/coupons')->with('error', 'Editors are not allowed to delete records.');
        }

        if (!service('coupons')->deleteCoupon($couponId)) {
            return redirect()->to('/admin/coupons')->with('error', 'Coupon could not be deleted.');
        }

        return redirect()->to('/admin/coupons')->with('success', 'Coupon deleted.');
    }

    private function canDelete(): bool
    {
        return (string)session('role') !== 'editor';
    }
}
