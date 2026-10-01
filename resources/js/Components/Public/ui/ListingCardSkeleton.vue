<!-- resources/js/Components/Public/ui/ListingCardSkeleton.vue -->
<!--
  PHASE 16C — LISTING CARD SKELETON.

  Renamed from Skeletons/BusinessCardSkeleton.vue. Determined from CONSUMERS,
  not the filename: it was consumed only by GridSkeleton and its layout mirrors
  ListingCard, so it is a LISTING skeleton and the Business name was wrong.

  Built from the Skeleton primitive rather than repeating bespoke shimmer divs,
  and it approximates the card's structure without duplicating its markup.
-->
<script setup>
import Skeleton from './Skeleton.vue';

defineProps({
    /** 'default' mirrors the standard card; 'compact' the dense list row. */
    variant: {
        type: String,
        default: 'default',
        validator: (v) => ['default', 'compact'].includes(v),
    },
});
</script>

<template>
    <!-- aria-hidden: a placeholder must not pollute the accessibility tree. -->
    <div
        class="overflow-hidden rounded-card border border-hairline dark:border-hairline-dark bg-surface dark:bg-gray-800"
        aria-hidden="true"
    >
        <template v-if="variant === 'default'">
            <Skeleton shape="h-40 w-full" rounded="none" />

            <div class="p-4 space-y-3">
                <div class="flex items-center gap-2">
                    <Skeleton shape="h-4 w-4/5" />
                    <Skeleton shape="h-5 w-16" rounded="pill" />
                </div>
                <Skeleton shape="h-3 w-1/2" />
                <Skeleton shape="h-3 w-2/3" />
                <div class="flex items-center gap-3 pt-3 border-t border-hairline dark:border-hairline-dark">
                    <Skeleton shape="h-3 w-16" />
                    <Skeleton shape="h-3 w-12" />
                </div>
            </div>
        </template>

        <template v-else>
            <div class="flex items-center gap-3 p-3">
                <Skeleton shape="h-14 w-14" rounded="control" />
                <div class="flex-1 space-y-2">
                    <Skeleton shape="h-3.5 w-3/4" />
                    <Skeleton shape="h-3 w-1/2" />
                </div>
            </div>
        </template>
    </div>
</template>
