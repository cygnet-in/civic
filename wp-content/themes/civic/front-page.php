<?php
/**
 * Front Page Template
 *
 * Theme: Civic Management System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="civic-home">

	<!-- Hero Section -->
	<section class="civic-home-hero">
		<div class="civic-home__container civic-home-hero__inner">

			<div class="civic-home-hero__content">

				<p class="civic-home-hero__eyebrow">Councillor for Dún Laoghaire</p>

				<h1 class="civic-home-hero__title">
					Thomas Joseph
				</h1>

				<p class="civic-home-hero__tagline">
					Bridging the gap between community concerns and resolutions.
				</p>

				<p class="civic-home-hero__text">
					This website provides a space for the constituents of Dún Laoghaire to report local concerns,
					take part in community discussions, and follow the progress of actions taken through council
					meetings and public engagements.
				</p>				

				<div class="civic-home-hero__actions">
					<a class="civic-home-btn civic-home-btn--primary" href="<?php echo esc_url( home_url( '/representation/' ) ); ?>">
						Submit a Representation
					</a>

					<a class="civic-home-btn civic-home-btn--secondary" href="<?php echo esc_url( home_url( '/threads/' ) ); ?>">
						View Public Consultations
					</a>
				</div>
				<ul class="civic-home-hero__highlights">
					<li>Accessible Representation</li>
					<li>Community Feedback</li>
					<li>Local Action</li>
				</ul>
			</div>

			<div class="civic-home-hero__photo-wrap">
				<img
					class="civic-home-hero__photo"
					src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/thomas-joseph.jpg' ); ?>"
					alt="Councillor Thomas Joseph"
				>
			</div>

		</div>
	</section>

	<!-- Quick Actions -->
	 <section class="civic-home-section civic-home-section--quick">
		<div class="civic-home__container">

			<div class="civic-home-section__header">
				<p class="civic-home-section__eyebrow">Get Started</p>
				<h2 class="civic-home-section__title">How can we help?</h2>
			</div>

			<div class="civic-home-services">

				<a class="civic-home-service civic-home-service--rep" href="<?php echo esc_url( home_url( '/representation/' ) ); ?>">
					<span class="civic-home-service__icon" aria-hidden="true"></span>
					<span class="civic-home-service__label">Digital Service</span>
					<strong>Submit a Representation</strong>
					<small>Raise a local issue or concern directly with the office.</small>
					<span class="civic-home-service__link">Start now →</span>
				</a>

				<a class="civic-home-service civic-home-service--consultation" href="<?php echo esc_url( home_url( '/threads/' ) ); ?>">
					<span class="civic-home-service__icon" aria-hidden="true"></span>
					<span class="civic-home-service__label">Public Feedback</span>
					<strong>Public Consultations</strong>
					<small>Share your views on local proposals and community discussions.</small>
					<span class="civic-home-service__link">View consultations →</span>
				</a>

				<a class="civic-home-service civic-home-service--event" href="<?php echo esc_url( home_url( '/events/' ) ); ?>">
					<span class="civic-home-service__icon" aria-hidden="true"></span>
					<span class="civic-home-service__label">Community</span>
					<strong>Community Events</strong>
					<small>Discover public meetings, programmes, and local community events.</small>
					<span class="civic-home-service__link">Explore events →</span>
				</a>

				<a class="civic-home-service civic-home-service--schedule" href="<?php echo esc_url( home_url( '/schedules/' ) ); ?>">
					<span class="civic-home-service__icon" aria-hidden="true"></span>
					<span class="civic-home-service__label">Public Activity</span>
					<strong>Schedules & Public Activities</strong>
					<small>Keep track of public activities, meetings, and upcoming engagements.</small>
					<span class="civic-home-service__link">View schedules →</span>
				</a>

			</div>
		</div>
	</section>

	<!-- About Section -->
	<section class="civic-home-section civic-home-about">
		<div class="civic-home__container civic-home-about__inner">

			<div class="civic-home-about__photo-wrap">
				<img
					class="civic-home-about__photo"
					src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/thomas-joseph-2.jpg' ); ?>"
					alt="Thomas Joseph meeting with the community"
				>
			</div>

			<div class="civic-home-about__content">			

				<p class="civic-home-section__eyebrow">About Thomas Joseph</p>

				<h2 class="civic-home-section__title">
					Public service, community engagement, and sustainable local action
				</h2>

				<p>
					I am an elected Councillor for Dún Laoghaire, a Peace Commissioner, and a Commissioner for Oaths.
					Outside of politics, I am an IT architect.
				</p>

				<p>
					I hold a Master's in Computer Applications, an MSc in Business from University College Dublin,
					a Postgraduate Certificate in Climate Entrepreneurship from Trinity College Dublin, and a
					Certificate in Climate Crisis and Local Government from University College Cork.
				</p>

				<p>
					My passion for public service, community engagement, and creating opportunities for people of
					all backgrounds led me to get involved in politics and keeps me committed to building inclusive,
					safe, and sustainable communities.
				</p>
				
			</div>

		</div>
	</section>

	<section class="civic-home-stats">

		<div class="civic-home__container">

			<div class="civic-home-section__header">
				<p class="civic-home-section__eyebrow">
					Community at a Glance
				</p>

				<h2 class="civic-home-section__title">
					Civic Platform Activity
				</h2>

				<p class="civic-home-section__description">
					Live statistics from community engagement across the platform.
				</p>
			</div>

			<div class="civic-stats-grid">

				<?php echo do_shortcode('[civic_statistics]'); ?>

			</div>

		</div>

	</section>

	<!-- Latest Activity -->
	<section class="civic-home-section civic-home-section--activity">
		<div class="civic-home__container">

			<div class="civic-home-section__header">
				<p class="civic-home-section__eyebrow">Latest Activity</p>
				<h2 class="civic-home-section__title">Stay informed and involved</h2>
			</div>

			<div class="civic-home-latest">

				<div class="civic-home-latest__block">
					<h3>Latest Consultations</h3>
					<?php echo do_shortcode( '[civic_threads limit="3"]' ); ?>
					<a class="civic-home-latest__view-all" href="<?php echo esc_url( home_url( '/threads/' ) ); ?>">
						View all consultations →
					</a>
				</div>

				<div class="civic-home-latest__block">
					<h3>Community Events</h3>
					<?php echo do_shortcode( '[civic_events limit="3"]' ); ?>
					<a class="civic-home-latest__view-all" href="<?php echo esc_url( home_url( '/events/' ) ); ?>">
						View all events →
					</a>
				</div>

				<div class="civic-home-latest__block">
					<h3>Upcoming Schedules</h3>
					<?php echo do_shortcode( '[civic_schedules limit="3"]' ); ?>
					<a class="civic-home-latest__view-all" href="<?php echo esc_url( home_url( '/schedules/' ) ); ?>">
						View all schedules →
					</a>
				</div>

			</div>

		</div>
	</section>


	<!-- Latest News / Blog Posts -->
	<section class="civic-home-section civic-home-news">
		<div class="civic-home__container">

			<div class="civic-home-section__header civic-home-news__header">
				<div>
					<p class="civic-home-section__eyebrow">News & Updates</p>

					<h2 class="civic-home-section__title">
						Latest from Thomas Joseph
					</h2>

					<p class="civic-home-section__description">
						Updates on council work, community matters, local initiatives,
						and issues affecting Dún Laoghaire.
					</p>
				</div>

				<a
					class="civic-home-news__view-all"
					href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>"
				>
					View all updates →
				</a>
			</div>

			<div class="civic-home-news__grid">

				<?php
				$civic_news_query = new WP_Query(
					array(
						'post_type'           => 'post',
						'post_status'         => 'publish',
						'posts_per_page'      => 3,
						'ignore_sticky_posts' => true,
					)
				);

				if ( $civic_news_query->have_posts() ) :
					while ( $civic_news_query->have_posts() ) :
						$civic_news_query->the_post();
						?>

						<article <?php post_class( 'civic-home-news-card' ); ?>>

							<a
								class="civic-home-news-card__image"
								href="<?php the_permalink(); ?>"
								aria-label="<?php echo esc_attr( get_the_title() ); ?>"
							>
								<?php if ( has_post_thumbnail() ) : ?>
									<?php
									the_post_thumbnail(
										'medium_large',
										array(
											'loading' => 'lazy',
										)
									);
									?>
								<?php else : ?>
									<span class="civic-home-news-card__placeholder" aria-hidden="true"></span>
								<?php endif; ?>
							</a>

							<div class="civic-home-news-card__content">

								<time
									class="civic-home-news-card__date"
									datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"
								>
									<?php echo esc_html( get_the_date( 'j F Y' ) ); ?>
								</time>

								<h3 class="civic-home-news-card__title">
									<a href="<?php the_permalink(); ?>">
										<?php the_title(); ?>
									</a>
								</h3>

								<p class="civic-home-news-card__excerpt">
									<?php
									echo esc_html(
										wp_trim_words(
											get_the_excerpt(),
											22,
											'…'
										)
									);
									?>
								</p>

								<a
									class="civic-home-news-card__link"
									href="<?php the_permalink(); ?>"
								>
									Read article →
								</a>

							</div>

						</article>

						<?php
					endwhile;

					wp_reset_postdata();
				else :
					?>
					<p class="civic-home-news__empty">
						No updates have been published yet.
					</p>
				<?php endif; ?>

			</div>

		</div>
	</section>

	<!-- Bottom CTA -->
	<section class="civic-home-cta">
    <div class="civic-home__container">

        <div class="civic-home-cta__panel">

            <div class="civic-home-cta__content">
                <p class="civic-home-cta__eyebrow">
                    Get in touch
                </p>

                <h2 class="civic-home-cta__title">
                    Have a local issue to raise?
                </h2>

                <p class="civic-home-cta__text">
                    Submit your concern, contact the office, or search current
                    consultations, events, schedules, and community updates.
                </p>
            </div>

            <div class="civic-home-cta__actions">

                <a
                    class="civic-home-btn civic-home-btn--light"
                    href="https://tjoseph.ie/representation/"
                >
                    Submit a Representation
                    <span aria-hidden="true">→</span>
                </a>

                <form
                    class="civic-search-form__form"
                    method="get"
                    action="https://tjoseph.ie/search-results/"
                    role="search"
                >
                    <label
                        class="screen-reader-text"
                        for="civic-search-input"
                    >
                        Search the civic platform
                    </label>

                    <input
                        class="civic-search-form__input"
                        type="search"
                        id="civic-search-input"
                        name="civic_search"
                        value=""
                        placeholder="Search the civic platform"
                    >

                    <button
                        class="civic-search-form__button"
                        type="submit"
                    >
                        Search
                    </button>
                </form>

            </div>

        </div>

    </div>
</section>

</main>

<?php
get_footer();