<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServicePackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Generates official LINE Flex Message (Bubble & Carousel) JSON for properties.
 */
class FlexMessageApiController extends Controller
{
    public function show(ServicePackage $package): JsonResponse
    {
        abort_unless($package->is_active && $package->is_published, 404);
        $package->load('category:id,name_th,slug');

        $bubble = $this->buildPropertyBubble($package);

        return response()->json([
            'type' => 'flex',
            'altText' => "🏡 {$package->name_th} - ฿".number_format($package->price),
            'contents' => $bubble,
        ]);
    }

    public function carousel(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 5), 10);
        $categorySlug = $request->query('category_slug');

        $query = ServicePackage::query()
            ->active()->published()->effective()
            ->with('category:id,name_th,slug')
            ->where('availability', 'available');

        if ($categorySlug) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
        }

        $items = $query->latest('updated_at')->take($limit)->get();

        $bubbles = $items->map(fn (ServicePackage $pkg) => $this->buildPropertyBubble($pkg))->values()->all();

        return response()->json([
            'type' => 'flex',
            'altText' => '🏢 รายการอสังหาริมทรัพย์แนะนำจาก บิว Property',
            'contents' => [
                'type' => 'carousel',
                'contents' => $bubbles,
            ],
        ]);
    }

    /**
     * Build an elegant LINE Flex Bubble for a property.
     *
     * @return array<string, mixed>
     */
    private function buildPropertyBubble(ServicePackage $item): array
    {
        $categoryName = $item->category?->name_th ?? 'อสังหาริมทรัพย์';
        $transactionBadge = $item->transaction_type === 'rent' ? 'สำหรับเช่า' : 'สำหรับขาย';
        $badgeColor = $item->transaction_type === 'rent' ? '#2563EB' : '#D97706';
        $priceText = $item->price ? '฿'.number_format($item->price) : 'ติดต่อสอบถาม';
        if ($item->transaction_type === 'rent' && $item->price) {
            $priceText .= '/เดือน';
        }

        $specs = [];
        if ($item->bedrooms) {
            $specs[] = "🛏️ {$item->bedrooms} นอน";
        }
        if ($item->bathrooms) {
            $specs[] = "🚿 {$item->bathrooms} น้ำ";
        }
        if ($item->usable_area_sqm) {
            $specs[] = "📐 {$item->usable_area_sqm} ตร.ม.";
        }
        if ($item->land_area_sqw) {
            $specs[] = "🌳 {$item->land_area_sqw} ตร.ว.";
        }
        $specText = implode('  |  ', $specs);

        return [
            'type' => 'bubble',
            'size' => 'kilo',
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'backgroundColor' => '#0F172A',
                'paddingTop' => '14px',
                'paddingBottom' => '14px',
                'paddingStart' => '16px',
                'paddingEnd' => '16px',
                'contents' => [
                    [
                        'type' => 'box',
                        'layout' => 'horizontal',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => $categoryName,
                                'color' => '#94A3B8',
                                'size' => 'xs',
                                'weight' => 'bold',
                                'flex' => 1,
                            ],
                            [
                                'type' => 'text',
                                'text' => $transactionBadge,
                                'color' => '#FFFFFF',
                                'size' => 'xxs',
                                'weight' => 'bold',
                                'align' => 'end',
                                'backgroundColor' => $badgeColor,
                                'cornerRadius' => '4px',
                                'paddingStart' => '6px',
                                'paddingEnd' => '6px',
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'text' => $item->project_name ?: $item->name_th,
                        'color' => '#FFFFFF',
                        'size' => 'md',
                        'weight' => 'bold',
                        'margin' => 'sm',
                        'wrap' => true,
                    ],
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'md',
                'paddingAll' => '16px',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => $priceText,
                        'size' => 'xl',
                        'weight' => 'bold',
                        'color' => '#D97706',
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'spacing' => 'xs',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => '📍 '.($item->location_text ?: ($item->district.' '.$item->province)),
                                'size' => 'xs',
                                'color' => '#64748B',
                                'wrap' => true,
                            ],
                            [
                                'type' => 'text',
                                'text' => $specText ?: '✨ สภาพพร้อมอยู่ ทำเลดี เดินทางสะดวก',
                                'size' => 'xs',
                                'color' => '#334155',
                                'weight' => 'bold',
                                'margin' => 'xs',
                            ],
                        ],
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'sm',
                'paddingAll' => '12px',
                'contents' => [
                    [
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => '#0F172A',
                        'height' => 'sm',
                        'action' => [
                            'type' => 'message',
                            'label' => 'ดูรายละเอียดตัวนี้',
                            'text' => "ขอดูรายละเอียด {$item->name_th} (รหัส {$item->code}) ครับ",
                        ],
                    ],
                    [
                        'type' => 'button',
                        'style' => 'secondary',
                        'height' => 'sm',
                        'action' => [
                            'type' => 'message',
                            'label' => 'นัดชม / ติดต่อแอดมิน',
                            'text' => "สนใจนัดชมห้อง {$item->name_th} ขอคุยกับเจ้าหน้าที่ครับ",
                        ],
                    ],
                ],
            ],
        ];
    }
}
