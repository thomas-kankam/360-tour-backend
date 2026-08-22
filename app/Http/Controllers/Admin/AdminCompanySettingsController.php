<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Traits\Helpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCompanySettingsController extends Controller
{
    use Helpers;

    public function show(): JsonResponse
    {
        $settings = CompanySetting::current()->mergedSettings();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Company settings retrieved', [
            'settings' => $settings,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings' => 'required|array',
            'settings.legal_name' => 'nullable|string|max:255',
            'settings.tagline' => 'nullable|string|max:255',
            'settings.email' => 'nullable|email|max:255',
            'settings.phone' => 'nullable|string|max:50',
            'settings.website' => 'nullable|string|max:255',
            'settings.address_line_1' => 'nullable|string|max:255',
            'settings.address_line_2' => 'nullable|string|max:255',
            'settings.tax_id' => 'nullable|string|max:255',
            'settings.invoice_logo' => 'nullable|string',
            'settings.bank_name' => 'nullable|string|max:255',
            'settings.bank_account' => 'nullable|string|max:255',
            'settings.bank_routing' => 'nullable|string|max:255',
            'settings.payment_notes' => 'nullable|string',
            'settings.paypal_or_mobile_money' => 'nullable|string|max:255',
            'settings.invoice_terms' => 'nullable|string',
            'settings.default_currency' => 'nullable|string|max:10',
            'settings.default_tax_percent' => 'nullable|numeric|min:0',
        ]);

        $record = CompanySetting::current();
        $settings = array_merge($record->mergedSettings(), $data['settings']);

        if (! empty($settings['invoice_logo'])) {
            $settings['invoice_logo'] = static::persistStoredImageValue($settings['invoice_logo'], 'logo')
                ?? static::normalizePublicUrl($settings['invoice_logo']);
        }

        $record->update(['settings' => $settings]);

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Company settings saved', [
            'settings' => $record->fresh()->mergedSettings(),
        ]);
    }
}
