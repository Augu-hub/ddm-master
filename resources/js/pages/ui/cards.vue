<template>
    <VerticalLayout>
        <PageTitle title="Cards" subtitle="Base UI" />
        <b-row>
            <b-col sm="6" lg="3">
                <b-card no-body class="d-block">
                    <img class="card-img-top" :src="small1" alt="Card img cap" />
                    <b-card-body>
                        <b-card-title tag="h5">Card title</b-card-title>
                        <p class="card-text">
                            Some quick example text to build on the card title and make up the bulk of the card's content. Some quick example text to
                            build on the card title and make up.
                        </p>
                        <a href="javascript: void(0);" class="btn btn-primary">Button</a>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col sm="6" lg="3">
                <b-card no-body class="d-block">
                    <img class="card-img-top" :src="small2" alt="Card img cap" />
                    <b-card-body>
                        <b-card-title tag="h5">Card title</b-card-title>
                        <p class="card-text">Some quick example text to build on the card..</p>
                    </b-card-body>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">Cras justo odio</li>
                    </ul>
                    <b-card-body>
                        <a href="javascript: void(0);" class="card-link text-custom">Card link</a>{{ ' ' }}
                        <a href="javascript: void(0);" class="card-link text-custom">Another link</a>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col sm="6" lg="3">
                <b-card no-body class="d-block">
                    <img class="card-img-top" :src="small3" alt="Card img cap" />
                    <b-card-body>
                        <p class="card-text">
                            Some quick example text to build on the card title and make up the bulk of the card's content. Some quick example text to
                            build on the card title and make up.
                        </p>
                        <a href="javascript: void(0);" class="btn btn-primary">Button</a>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col sm="6" lg="3">
                <b-card no-body class="d-block">
                    <b-card-body>
                        <b-card-title tag="h5">Card title</b-card-title>
                        <h6 class="card-subtitle text-muted">Support card subtitle</h6>
                    </b-card-body>
                    <img class="img-fluid" :src="small4" alt="Card img cap" />
                    <b-card-body>
                        <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                        <a href="javascript: void(0);" class="card-link text-custom">Card link</a>{{ ' ' }}
                        <a href="javascript: void(0);" class="card-link text-custom">Another link</a>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>

        <b-row>
            <b-col sm="6">
                <b-card no-body class="card-body">
                    <b-card-title tag="h5">Special title treatment</b-card-title>
                    <p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
                    <a href="javascript: void(0);" class="btn btn-primary">Go somewhere</a>
                </b-card>
            </b-col>
            <b-col sm="6">
                <b-card no-body class="card-body">
                    <b-card-title tag="h5">Special title treatment</b-card-title>
                    <p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
                    <a href="javascript: void(0);" class="btn btn-primary">Go somewhere</a>
                </b-card>
            </b-col>
        </b-row>

        <b-row>
            <b-col md="4">
                <b-card no-body>
                    <h5 class="card-header bg-light">Featured</h5>
                    <b-card-body>
                        <b-card-title tag="h5">Special title treatment</b-card-title>
                        <p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
                        <a href="javascript: void(0);" class="btn btn-primary">Go somewhere</a>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col md="4">
                <b-card no-body>
                    <b-card-header class="bg-light"> Quote </b-card-header>
                    <b-card-body>
                        <blockquote class="card-bodyquote">
                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                            <footer>Someone famous in <cite title="Source Title">Source Title</cite></footer>
                        </blockquote>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col md="4">
                <b-card no-body>
                    <b-card-header class="bg-light"> Featured </b-card-header>
                    <b-card-body>
                        <a href="javascript: void(0);" class="btn btn-primary">Go somewhere</a>
                    </b-card-body>
                    <b-card-footer class="border-top border-light text-muted"> 2 days ago </b-card-footer>
                </b-card>
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <h4 class="mb-4 mt-3">Card Colored</h4>
            </b-col>
        </b-row>

        <b-row>
            <b-col lg="4" sm="6">
                <b-card no-body class="text-bg-secondary">
                    <b-card-body>
                        <b-card-title tag="h5">Special title treatment</b-card-title>
                        <p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
                        <a href="javascript: void(0);" class="btn btn-primary btn-sm">Button</a>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col lg="4" sm="6">
                <b-card no-body class="text-bg-primary">
                    <b-card-body>
                        <blockquote class="card-bodyquote">
                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                            <footer>Someone famous in <cite title="Source Title">Source Title</cite></footer>
                        </blockquote>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col lg="4" sm="6">
                <b-card no-body class="text-bg-success">
                    <b-card-body>
                        <blockquote class="card-bodyquote">
                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                            <footer>Someone famous in <cite title="Source Title">Source Title</cite></footer>
                        </blockquote>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col lg="4" sm="6">
                <b-card no-body class="text-bg-info">
                    <b-card-body>
                        <blockquote class="card-bodyquote mb-0">
                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                            <footer>Someone famous in <cite title="Source Title">Source Title</cite></footer>
                        </blockquote>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col lg="4" sm="6">
                <b-card no-body class="text-bg-warning">
                    <b-card-body>
                        <blockquote class="card-bodyquote mb-0">
                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                            <footer>Someone famous in <cite title="Source Title">Source Title</cite></footer>
                        </blockquote>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col lg="4" sm="6">
                <b-card no-body class="text-bg-danger">
                    <b-card-body>
                        <blockquote class="card-bodyquote mb-0">
                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                            <footer>Someone famous in <cite title="Source Title">Source Title</cite></footer>
                        </blockquote>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>

        <b-row>
            <b-col lg="4" sm="6">
                <b-card no-body class="text-bg-secondary bg-gradient">
                    <b-card-body>
                        <blockquote class="card-bodyquote mb-0">
                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                            <footer>Someone famous in <cite title="Source Title">Source Title</cite></footer>
                        </blockquote>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col lg="4" sm="6">
                <b-card no-body class="text-bg-primary bg-gradient">
                    <b-card-body>
                        <blockquote class="card-bodyquote mb-0">
                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                            <footer>Someone famous in <cite title="Source Title">Source Title</cite></footer>
                        </blockquote>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col lg="4" sm="6">
                <b-card no-body class="text-bg-success bg-gradient">
                    <b-card-body>
                        <blockquote class="card-bodyquote mb-0">
                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                            <footer>Someone famous in <cite title="Source Title">Source Title</cite></footer>
                        </blockquote>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col lg="4" sm="6">
                <b-card no-body class="text-bg-info bg-gradient">
                    <b-card-body>
                        <blockquote class="card-bodyquote mb-0">
                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                            <footer>Someone famous in <cite title="Source Title">Source Title</cite></footer>
                        </blockquote>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col lg="4" sm="6">
                <b-card no-body class="text-bg-warning bg-gradient">
                    <b-card-body>
                        <blockquote class="card-bodyquote mb-0">
                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                            <footer>Someone famous in <cite title="Source Title">Source Title</cite></footer>
                        </blockquote>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col lg="4" sm="6">
                <b-card no-body class="text-bg-danger bg-gradient">
                    <b-card-body>
                        <blockquote class="card-bodyquote mb-0">
                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                            <footer>Someone famous in <cite title="Source Title">Source Title</cite></footer>
                        </blockquote>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <h4 class="mb-4 mt-3">Card Bordered</h4>
            </b-col>
        </b-row>

        <b-row>
            <b-col md="4">
                <b-card no-body class="border-secondary border">
                    <b-card-body>
                        <b-card-title tag="h5">Special title treatment</b-card-title>
                        <p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
                        <a href="javascript: void(0);" class="btn btn-secondary btn-sm">Button</a>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col md="4">
                <b-card no-body class="border-primary border border-dashed">
                    <b-card-body>
                        <h5 class="card-title text-primary">Special title treatment</h5>
                        <p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
                        <a href="javascript: void(0);" class="btn btn-primary btn-sm">Button</a>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col md="4">
                <b-card no-body class="border-success border">
                    <b-card-body>
                        <h5 class="card-title text-success">Special title treatment</h5>
                        <p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
                        <a href="javascript: void(0);" class="btn btn-success btn-sm">Button</a>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <h4 class="mb-4 mt-3">Horizontal Card</h4>
            </b-col>
        </b-row>

        <b-row>
            <b-col lg="6">
                <b-card no-body>
                    <b-row class="g-0 align-items-center">
                        <b-col md="4">
                            <img :src="small4" class="img-fluid rounded-start" alt="..." />
                        </b-col>
                        <b-col md="8">
                            <b-card-body>
                                <b-card-title tag="h5">Card title</b-card-title>
                                <p class="card-text">
                                    This is a wider card with supporting text below as a natural lead-in to additional content. This content is a
                                    little bit longer.
                                </p>
                                <p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p>
                            </b-card-body>
                        </b-col>
                    </b-row>
                </b-card>
            </b-col>
            <b-col lg="6">
                <b-card no-body>
                    <b-row class="g-0 align-items-center">
                        <b-col md="8">
                            <b-card-body>
                                <b-card-title tag="h5">Card title</b-card-title>
                                <p class="card-text">
                                    This is a wider card with supporting text below as a natural lead-in to additional content. This content is a
                                    little bit longer.
                                </p>
                                <p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p>
                            </b-card-body>
                        </b-col>
                        <b-col md="4">
                            <img :src="small1" class="img-fluid rounded-end" alt="..." />
                        </b-col>
                    </b-row>
                </b-card>
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <h4 class="mb-4 mt-3">Stretched link</h4>
            </b-col>
        </b-row>

        <b-row>
            <b-col sm="6" lg="3">
                <b-card no-body>
                    <img :src="small2" class="card-img-top" alt="..." />
                    <b-card-body>
                        <b-card-title tag="h5">Card with stretched link</b-card-title>
                        <a href="#" class="btn btn-primary stretched-link mt-2">Go somewhere</a>
                    </b-card-body>
                </b-card>
            </b-col>
            <b-col sm="6" lg="3">
                <b-card no-body>
                    <img :src="small3" class="card-img-top" alt="..." />
                    <b-card-body>
                        <b-card-title tag="h5"><a href="#" class="text-success stretched-link">Card with stretched link</a></b-card-title>
                        <p class="card-text">Some quick example text to build on the card up the bulk of the card's content.</p>
                    </b-card-body>
                </b-card>
            </b-col>
            <b-col sm="6" lg="3">
                <b-card no-body>
                    <img :src="small4" class="card-img-top" alt="..." />
                    <b-card-body>
                        <b-card-title tag="h5">Card with stretched link</b-card-title>
                        <a href="#" class="btn btn-info stretched-link mt-2">Go somewhere</a>
                    </b-card-body>
                </b-card>
            </b-col>
            <b-col sm="6" lg="3">
                <b-card no-body>
                    <img :src="small1" class="card-img-top" alt="..." />
                    <b-card-body>
                        <b-card-title tag="h5"><a href="#" class="stretched-link">Card with stretched link</a></b-card-title>
                        <p class="card-text">Some quick example text to build on the card up the bulk of the card's content.</p>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <h4 class="mb-4 mt-3">Card Group</h4>
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <div class="card-group">
                    <b-card no-body class="d-block">
                        <img class="card-img-top" :src="small1" alt="Card img cap" />
                        <b-card-body>
                            <b-card-title tag="h5">Card title</b-card-title>
                            <p class="card-text">
                                This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little
                                bit longer.
                            </p>
                            <p class="card-text">
                                <small class="text-muted">Last updated 3 mins ago</small>
                            </p>
                        </b-card-body>
                    </b-card>
                    <b-card no-body class="d-block">
                        <img class="card-img-top" :src="small2" alt="Card img cap" />
                        <b-card-body>
                            <b-card-title tag="h5">Card title</b-card-title>
                            <p class="card-text">This card has supporting text below as a natural lead-in to additional content.</p>
                            <p class="card-text">
                                <small class="text-muted">Last updated 3 mins ago</small>
                            </p>
                        </b-card-body>
                    </b-card>
                    <b-card no-body class="d-block">
                        <img class="card-img-top" :src="small3" alt="Card img cap" />
                        <b-card-body>
                            <b-card-title tag="h5">Card title</b-card-title>
                            <p class="card-text">
                                This is a wider card with supporting text below as a natural lead-in to additional content. This card has even longer
                                content than the first to show that equal height action.
                            </p>
                            <p class="card-text">
                                <small class="text-muted">Last updated 3 mins ago</small>
                            </p>
                        </b-card-body>
                    </b-card>
                </div>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import VerticalLayout from '@/layouts/VerticalLayout.vue';

import PageTitle from '@/components/PageTitle.vue';
import small1 from '@/images/small/small-1.jpg';
import small2 from '@/images/small/small-2.jpg';
import small3 from '@/images/small/small-3.jpg';
import small4 from '@/images/small/small-4.jpg';
</script>
