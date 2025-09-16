<template>
    <VerticalLayout>
        <PageTitle title="Ratings" subtitle="Extended UI" />
        <b-row class="row-cols-lg-2">
            <b-col>
                <UICard title="Basic 5 star rater">
                    <div>
                        <div id="rater" dir="ltr"></div>
                    </div>
                </UICard>
            </b-col>

            <b-col>
                <UICard title="5 star rater with step">
                    <div>
                        <div id="rater-step" dir="ltr"></div>
                    </div>
                </UICard>
            </b-col>

            <b-col>
                <UICard title="Custom Messages Example">
                    <div>
                        <div id="rater2" dir="ltr"></div>
                    </div>
                </UICard>
            </b-col>

            <b-col>
                <UICard title="Unlimited Number">
                    <div>
                        <div id="rater3" dir="ltr"></div>
                    </div>
                </UICard>
            </b-col>

            <b-col>
                <UICard title="Random Number">
                    <div>
                        <div id="rater4" dir="ltr"></div>
                    </div>
                </UICard>
            </b-col>

            <b-col>
                <UICard title="On Hover Event Example">
                    <div dir="ltr">
                        <div id="rater5" class="align-middle"></div>
                        <span class="live-rating badge bg-info ms-2 align-middle"></span>
                    </div>
                </UICard>
            </b-col>

            <b-col>
                <UICard title="Clear/Reset Rater Example">
                    <div dir="ltr">
                        <div id="rater6" class="align-middle"></div>
                        <span class="clear-rating"></span>
                        <b-button id="rater6-button" variant="light" size="sm" class="ms-2">Reset</b-button>
                    </div>
                </UICard>
            </b-col>

            <b-col>
                <UICard title="Right to left support">
                    <div dir="rtl">
                        <span id="rater7"></span>
                    </div>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import raterJs from 'rater-js/index';
import { onMounted } from 'vue';

onMounted(() => {
    // Basic 5 star rater
    if (document.querySelector('#rater')) {
        raterJs({
            starSize: 32,
            element: document.querySelector('#rater'),
            rateCallback: function rateCallback(rating: number, done: any) {
                this.setRating(rating);
                done();
            },
        });
    }

    // 5 star rater with step
    if (document.querySelector('#rater-step')) {
        raterJs({
            starSize: 32,
            step: 0.5,
            element: document.querySelector('#rater-step'),
            rateCallback: function rateCallback(rating: number, done: any) {
                this.setRating(rating);
                done();
            },
        });
    }

    // Custom Messages Example
    if (document.querySelector('#rater2')) {
        const starRating2 = raterJs({
            max: 5,
            rating: 4,
            element: document.querySelector('#rater2'),
            disableText: 'Custom disable text!',
            ratingText: 'My custom rating text {rating}',
            showToolTip: true,
            rateCallback: function rateCallback(rating: number, done: any) {
                starRating2.setRating(rating);
                starRating2.disable();
                done();
            },
        });
    }

    // Unlimited Number
    if (document.querySelector('#rater3')) {
        raterJs({
            max: 16,
            readOnly: true,
            rating: 4.4,
            element: document.querySelector('#rater3'),
        });
    }

    // Random Number
    const myDataService = {
        rate: function () {
            return {
                then: function (callback: any) {
                    setTimeout(function () {
                        callback(Math.random() * 5);
                    }, 1000);
                },
            };
        },
    };

    if (document.querySelector('#rater4')) {
        const starRating4 = raterJs({
            isBusyText: 'Rating in progress. Please wait...',
            element: document.querySelector('#rater4'),
            rateCallback: function rateCallback(rating: number, done: any) {
                starRating4.setRating(rating);
                myDataService.rate().then(function (avgRating: number) {
                    starRating4.setRating(avgRating);
                    done();
                });
            },
        });
    }

    // On Hover Event Example
    if (document.querySelector('#rater5')) {
        const liveRating = document.querySelector('.live-rating') as HTMLElement;
        raterJs({
            element: document.querySelector('#rater5'),
            rateCallback: function rateCallback(rating: number, done: any) {
                this.setRating(rating);
                done();
            },
            onHover: function (currentIndex: string, currentRating: string) {
                liveRating.textContent = currentIndex;
            },
            onLeave: function (currentIndex: string, currentRating: string) {
                liveRating.textContent = currentRating;
            },
        });
    }

    // Clear/Reset Rater Example
    if (document.querySelector('#rater6')) {
        const starRatingReset = raterJs({
            starSize: 32,
            element: document.querySelector('#rater6'),
            rateCallback: function rateCallback(rating: number, done: any) {
                this.setRating(rating);
                done();
            },
        });

        const resetButton = document.querySelector('#rater6-button');
        if (resetButton)
            resetButton.addEventListener(
                'click',
                function () {
                    starRatingReset.clear();
                },
                false,
            );
    }

    // Right to left support
    if (document.querySelector('#rater7')) {
        raterJs({
            max: 6,
            reverse: true,
            element: document.querySelector('#rater7'),
            rateCallback: function rateCallback(rating: number, done: any) {
                this.setRating(rating);
                done();
            },
        });
    }
});
</script>
