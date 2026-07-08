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
				</div>

				<div class="civic-home-latest__block">
					<h3>Community Events</h3>
					<?php echo do_shortcode( '[civic_events limit="3"]' ); ?>
				</div>

				<div class="civic-home-latest__block">
					<h3>Upcoming Schedules</h3>
					<?php echo do_shortcode( '[civic_schedules limit="3"]' ); ?>
				</div>

			</div>

		</div>
	</section>

	<!-- Bottom CTA -->
	<section class="civic-home-cta">
		<div class="civic-home__container civic-home-cta__inner">
			<div>
				<h2>Have a local issue to raise?</h2>
				<p>Submit your concern and help us understand what matters most in the community.</p>
			</div>

			<a class="civic-home-btn civic-home-btn--light" href="<?php echo esc_url( home_url( '/representation/' ) ); ?>">
				Submit a Representation
			</a>
		</div>
	</section>

</main>

<?php
get_footer();