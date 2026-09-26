<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\ExploreService;
use App\Services\IpLocationService;
use Inertia\Inertia;

class ExploreController extends Controller
{
    public function __construct(
        private ExploreService $explore,
        private IpLocationService $ipLocation,
    ) {
    }

    public function index()
    {
        $city = $this->ipLocation->resolveCity(request()->ip());
        $feed = $this->explore->buildFeed($city);

        // SEO copy
        if ($feed['mode'] === 'city' && $feed['city']) {
            $title = 'Explore ' . $feed['city']['name'];
            $subtitle = 'Discover the best businesses in ' . $feed['city']['name'];
            $seoTitle = 'Explore ' . $feed['city']['name'] . ' · Omniscient';
            $seoDescription = 'Discover restaurants, hotels, supermarkets, and more in ' . $feed['city']['name'] . ', Cameroon.';
        } else {
            $title = 'Explore Businesses';
            $subtitle = 'Discover the best local businesses across Cameroon';
            $seoTitle = 'Explore Businesses · Omniscient';
            $seoDescription = 'Discover restaurants, hotels, supermarkets, and more across Cameroon.';
        }

        return Inertia::render('Public/Explore', [
            'mode' => $feed['mode'],
            'city' => $feed['city'],
            'rows' => $feed['rows'],
            'hero' => [
                'title' => $title,
                'subtitle' => $subtitle,
            ],
            'seo' => [
                'title' => $seoTitle,
                'description' => $seoDescription,
                'canonical' => url('/explore'),
            ],
        ]);
    }
}