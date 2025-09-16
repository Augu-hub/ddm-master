<template>
    <VerticalLayout>
        <PageTitle title="Carousel" subtitle="Base UI" />
        <b-row>
            <b-col lg="6">
                <UICard title="Slides Only">
                    <b-carousel>
                        <b-carousel-slide :img-src="small1" />
                        <b-carousel-slide :img-src="small2" />
                        <b-carousel-slide :img-src="small3" />
                    </b-carousel>
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="With Controls">
                    <b-carousel controls>
                        <b-carousel-slide :img-src="small4" />
                        <b-carousel-slide :img-src="small1" />
                        <b-carousel-slide :img-src="small2" />
                    </b-carousel>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col lg="6">
                <UICard title="With Indicators">
                    <b-carousel controls indicators>
                        <b-carousel-slide :img-src="small3" />
                        <b-carousel-slide :img-src="small2" />
                        <b-carousel-slide :img-src="small1" />
                    </b-carousel>
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="With Captions">
                    <b-carousel controls>
                        <b-carousel-slide
                            :img-src="small1"
                            text="Lorem ipsum dolor sit amet, consectetur adipiscing elit"
                            caption-html="<h3 class='text-white'>First slide label</h3>"
                        />
                        <b-carousel-slide
                            :img-src="small3"
                            text="Lorem ipsum dolor sit amet, consectetur adipiscing elit"
                            caption-html="<h3 class='text-white'>Second slide label</h3>"
                        />
                        <b-carousel-slide
                            :img-src="small2"
                            text="Lorem ipsum dolor sit amet, consectetur adipiscing elit"
                            caption-html="<h3 class='text-white'>Third slide label</h3>"
                        />
                    </b-carousel>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col lg="6">
                <UICard title="Crossfade">
                    <b-carousel controls fade>
                        <b-carousel-slide :img-src="small1" />
                        <b-carousel-slide :img-src="small2" />
                        <b-carousel-slide :img-src="small3" />
                    </b-carousel>
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="Individual Interval">
                    <b-carousel controls :interval="1000">
                        <b-carousel-slide :img-src="small4" />
                        <b-carousel-slide :img-src="small2" />
                        <b-carousel-slide :img-src="small1" />
                    </b-carousel>
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="Dark Variant">
                    <b-carousel controls indicators class="carousel-dark">
                        <b-carousel-slide
                            :img-src="small5"
                            text="Some representative placeholder content for the first slide."
                            caption-html="<h5>First slide label</h5>"
                        />
                        <b-carousel-slide
                            :img-src="small6"
                            text="Some representative placeholder content for the second slide."
                            caption-html="<h5>Second slide label</h5>"
                        />
                        <b-carousel-slide
                            :img-src="small7"
                            text="Some representative placeholder content for the third slide."
                            caption-html="<h5>Third slide label</h5>"
                        />
                    </b-carousel>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import VerticalLayout from '@/layouts/VerticalLayout.vue';

import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import small1 from '@/images/small/small-1.jpg';
import small2 from '@/images/small/small-2.jpg';
import small3 from '@/images/small/small-3.jpg';
import small4 from '@/images/small/small-4.jpg';
import small5 from '@/images/small/small-5.jpg';
import small6 from '@/images/small/small-6.jpg';
import small7 from '@/images/small/small-7.jpg';
</script>
