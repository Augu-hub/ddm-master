<template>
    <VerticalLayout>
        <PageTitle title="Typography" subtitle="Base UI" />
        <b-row>
            <b-col cols="12">
                <UICard title="Display Headings">
                    <h1 class="display-1">Display 1</h1>
                    <p class="text-muted">
                        Suspendisse vel quam malesuada, aliquet sem sit amet, fringilla elit. Morbi tempor tincidunt tempor. Etiam id turpis viverra,
                        vulputate sapien nec, varius sem. Curabitur ullamcorper fringilla eleifend. In ut eros hendrerit est consequat posuere et at
                        velit.
                    </p>

                    <div class="clearfix"></div>

                    <h1 class="display-2">Display 2</h1>
                    <p class="text-muted">
                        In nec rhoncus eros. Vestibulum eu mattis nisl. Quisque viverra viverra magna nec pulvinar. Maecenas pellentesque porta augue,
                        consectetur facilisis diam porttitor sed. Suspendisse tempor est sodales augue rutrum tincidunt. Quisque a malesuada purus.
                    </p>

                    <div class="clearfix"></div>

                    <h1 class="display-3">Display 3</h1>
                    <p class="text-muted">
                        Vestibulum auctor tincidunt semper. Phasellus ut vulputate lacus. Suspendisse ultricies mi eros, sit amet tempor nulla varius
                        sed. Proin nisl nisi, feugiat quis bibendum vitae, dapibus in tellus.
                    </p>

                    <div class="clearfix"></div>

                    <h1 class="display-4">Display 4</h1>
                    <p class="text-muted mb-0">
                        Nulla et mattis nunc. Curabitur scelerisque commodo condimentum. Mauris blandit, velit a consectetur egestas, diam arcu
                        fermentum justo, eget ultrices arcu eros vel erat.
                    </p>

                    <div class="clearfix"></div>

                    <h1 class="display-5">Display 5</h1>
                    <p class="text-muted mb-0">
                        Nulla et mattis nunc. Curabitur scelerisque commodo condimentum. Mauris blandit, velit a consectetur egestas, diam arcu
                        fermentum justo, eget.
                    </p>

                    <div class="clearfix"></div>

                    <h1 class="display-6">Display 6</h1>
                    <p class="text-muted mb-0">
                        Nulla et mattis nunc. Curabitur scelerisque commodo condimentum. Mauris blandit, velit a consectetur egestas.
                    </p>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <UICard title="Headings">
                    <h1>This is a Heading 1</h1>
                    <p class="text-muted">
                        Suspendisse vel quam malesuada, aliquet sem sit amet, fringilla elit. Morbi tempor tincidunt tempor. Etiam id turpis viverra,
                        vulputate sapien nec, varius sem. Curabitur ullamcorper fringilla eleifend. In ut eros hendrerit est consequat posuere et at
                        velit.
                    </p>

                    <div class="clearfix"></div>

                    <h2>This is a Heading 2</h2>
                    <p class="text-muted">
                        In nec rhoncus eros. Vestibulum eu mattis nisl. Quisque viverra viverra magna nec pulvinar. Maecenas pellentesque porta augue,
                        consectetur facilisis diam porttitor sed. Suspendisse tempor est sodales augue rutrum tincidunt. Quisque a malesuada purus.
                    </p>

                    <div class="clearfix"></div>

                    <h3>This is a Heading 3</h3>
                    <p class="text-muted">
                        Vestibulum auctor tincidunt semper. Phasellus ut vulputate lacus. Suspendisse ultricies mi eros, sit amet tempor nulla varius
                        sed. Proin nisl nisi, feugiat quis bibendum vitae, dapibus in tellus.
                    </p>

                    <div class="clearfix"></div>

                    <h4>This is a Heading 4</h4>
                    <p class="text-muted">
                        Nulla et mattis nunc. Curabitur scelerisque commodo condimentum. Mauris blandit, velit a consectetur egestas, diam arcu
                        fermentum justo, eget ultrices arcu eros vel erat.
                    </p>

                    <div class="clearfix"></div>

                    <h5>This is a Heading 5</h5>
                    <p class="text-muted">
                        Quisque nec turpis at urna dictum luctus. Suspendisse convallis dignissim eros at volutpat. In egestas mattis dui. Aliquam
                        mattis dictum aliquet. Nulla sapien mauris, eleifend et sem ac, commodo dapibus odio. Vivamus pretium nec odio cursus
                        elementum. Suspendisse molestie ullamcorper ornare.
                    </p>

                    <div class="clearfix"></div>

                    <h6>This is a Heading 6</h6>
                    <p class="text-muted mb-0">
                        Donec ultricies, lacus id tempor condimentum, orci leo faucibus sem, a molestie libero lectus ac justo. ultricies mi eros, sit
                        amet tempor nulla varius sed. Proin nisl nisi, feugiat quis bibendum vitae, dapibus in tellus.
                    </p>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <UICard title="Inline Text Elements">
                    <p class="lead">Your title goes here</p>

                    <p>You can use the mark tag to <mark>highlight</mark> text.</p>
                    <p><del>This line of text is meant to be treated as deleted text.</del></p>
                    <p><s>This line of text is meant to be treated as no longer accurate.</s></p>
                    <p><ins>This line of text is meant to be treated as an addition to the document.</ins></p>
                    <p><u>This line of text will render as underlined</u></p>
                    <p><small>This line of text is meant to be treated as fine print.</small></p>
                    <p><strong>This line rendered as bold text.</strong></p>
                    <p><em>This line rendered as italicized text.</em></p>
                    Nulla <abbr title="attribute">attr</abbr> vitae elit libero, a pharetra augue.
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <UICard title="Naming a Source">
                    <figure>
                        <blockquote class="blockquote">
                            <p>A well-known quote, contained in a blockquote element.</p>
                        </blockquote>
                        <figcaption class="blockquote-footer">Someone famous in <cite title="Source Title">Source Title</cite></figcaption>
                    </figure>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="4">
                <UICard title="Unordered">
                    <ul>
                        <li>Lorem ipsum dolor sit amet</li>
                        <li>Consectetur adipiscing elit</li>
                        <li>Integer molestie lorem at massa</li>
                        <li>Facilisis in pretium nisl aliquet</li>
                        <li>
                            Nulla volutpat aliquam velit
                            <ul>
                                <li>Phasellus iaculis neque</li>
                                <li>Purus sodales ultricies</li>
                                <li>Vestibulum laoreet porttitor sem</li>
                                <li>Ac tristique libero volutpat at</li>
                            </ul>
                        </li>
                        <li>Faucibus porta lacus fringilla vel</li>
                        <li>Aenean sit amet erat nunc</li>
                        <li>Eget porttitor lorem</li>
                    </ul>
                </UICard>
            </b-col>

            <b-col xl="4">
                <UICard title="Ordered">
                    <ol>
                        <li>Lorem ipsum dolor sit amet</li>
                        <li>Consectetur adipiscing elit</li>
                        <li>Integer molestie lorem at massa</li>
                        <li>Facilisis in pretium nisl aliquet</li>
                        <li>
                            Nulla volutpat aliquam velit
                            <ol>
                                <li>Phasellus iaculis neque</li>
                                <li>Purus sodales ultricies</li>
                                <li>Vestibulum laoreet porttitor sem</li>
                                <li>Ac tristique libero volutpat at</li>
                            </ol>
                        </li>
                        <li>Faucibus porta lacus fringilla vel</li>
                        <li>Aenean sit amet erat nunc</li>
                        <li>Eget porttitor lorem</li>
                    </ol>
                </UICard>
            </b-col>

            <b-col xl="4">
                <UICard title="Unstyled">
                    <ul class="list-unstyled">
                        <li>Lorem ipsum dolor sit amet</li>
                        <li>
                            Integer molestie lorem at massa
                            <ul>
                                <li>Phasellus iaculis neque</li>
                            </ul>
                        </li>
                        <li>Faucibus porta lacus fringilla vel</li>
                        <li>Eget porttitor lorem</li>
                    </ul>

                    <h5>Inline</h5>
                    <p class="text-muted m-b-15">
                        Place all list items on a single line with <code> display: inline-block;</code>
                        and some light padding.
                    </p>

                    <ul class="list-inline">
                        <li class="list-inline-item">Lorem ipsum</li>
                        <li class="list-inline-item">Phasellus iaculis</li>
                        <li class="list-inline-item">Nulla volutpat</li>
                    </ul>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="4">
                <b-card no-body class="card-h-100">
                    <b-card-header class="border-bottom d-flex align-items-center border-dashed">
                        <h4 class="header-title">Abbreviations</h4>
                    </b-card-header>

                    <b-card-body>
                        <p class="text-muted">Add .initialism to an abbreviation for a slightly smaller font-size.</p>
                        <p><abbr title="attribute">attr</abbr></p>
                        <p><abbr title="HyperText Markup Language" class="initialism">HTML</abbr></p>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col xl="4">
                <b-card no-body class="card-h-100">
                    <b-card-header class="border-bottom d-flex align-items-center border-dashed">
                        <h4 class="header-title">Alignment</h4>
                    </b-card-header>

                    <b-card-body>
                        <p class="text-muted">Use text utilities as needed to change the alignment of your blockquote.</p>
                        <figure class="text-center">
                            <blockquote class="blockquote">
                                <p>A well-known quote, contained in a blockquote element.</p>
                            </blockquote>
                            <figcaption class="blockquote-footer">Someone famous in <cite title="Source Title">Source Title</cite></figcaption>
                        </figure>
                        <figure class="text-end">
                            <blockquote class="blockquote">
                                <p>A well-known quote, contained in a blockquote element.</p>
                            </blockquote>
                            <figcaption class="blockquote-footer">Someone famous in <cite title="Source Title">Source Title</cite></figcaption>
                        </figure>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col xl="4">
                <b-card no-body class="card-h-100">
                    <b-card-header class="border-bottom d-flex align-items-center border-dashed">
                        <h4 class="header-title">Inline</h4>
                    </b-card-header>

                    <b-card-body>
                        <p class="text-muted">
                            Remove a list's bullets and apply some light margin with a combination of two classes, .list-inline and .list-inline-item.
                        </p>
                        <ul class="list-inline">
                            <li class="list-inline-item">This is a list item.</li>
                            <li class="list-inline-item">And another one.</li>
                            <li class="list-inline-item">But they're displayed inline.</li>
                        </ul>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Blockquotes">
                    <blockquote class="blockquote">
                        <p class="mb-0">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                    </blockquote>
                    <figcaption class="blockquote-footer">Someone famous in <cite title="Source Title">Source Title</cite></figcaption>

                    <p class="text-muted m-b-15">Use text utilities as needed to change the alignment of your blockquote.</p>

                    <blockquote class="blockquote text-center">
                        <p class="mb-0">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                    </blockquote>
                    <figcaption class="blockquote-footer text-center">Someone famous in <cite title="Source Title">Source Title</cite></figcaption>

                    <blockquote class="blockquote text-end">
                        <p class="mb-0">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                    </blockquote>
                    <figcaption class="blockquote-footer text-end">Someone famous in <cite title="Source Title">Source Title</cite></figcaption>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Description List Alignment">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Description lists</dt>
                        <dd class="col-sm-9">A description list is perfect for defining terms.</dd>

                        <dt class="col-sm-3">Euismod</dt>
                        <dd class="col-sm-9">
                            <p>Vestibulum id ligula porta felis euismod semper eget lacinia odio sem nec elit.</p>
                            <p>Donec id elit non mi porta gravida at eget metus.</p>
                        </dd>

                        <dt class="col-sm-3">Malesuada porta</dt>
                        <dd class="col-sm-9">Etiam porta sem malesuada magna mollis euismod.</dd>

                        <dt class="col-sm-3 text-truncate">Truncated term is truncated</dt>
                        <dd class="col-sm-9">
                            Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus.
                        </dd>

                        <dt class="col-sm-3">Nesting</dt>
                        <dd class="col-sm-9">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Nested definition list</dt>
                                <dd class="col-sm-8">Aenean posuere, tortor sed cursus feugiat, nunc augue blandit nunc.</dd>
                            </dl>
                        </dd>
                    </dl>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
</script>
