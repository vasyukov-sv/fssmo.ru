<template>
	<section class="winners-section">
		<div class="container">
			<div class="tournament-winners">
				<h2>Победители турниров фссмо</h2>
				<div class="tournament-winners-list">
					<div class="row row-eq-height">
						<div class="tournament-winner-col" v-for="(item, i) in winners" :key="i">
							<div class="tournament-winner-item">
								<div class="tournament-winner-img-container">
									<div class="tournament-winner-img" v-if="item.image">
										<div class="tournament-winner-img-preview" :style="{ 'background-image': 'url('+item.image+')' }"></div>
									</div>
								</div>
								<div class="tournament-winner-caption">
									<div class="tournament-type">{{ item.discipline }}</div>
									<div class="tournament-winner-top">
										<div class="tournament-winner-name">{{ item.name }} {{ item.last_name }}</div>
										<div class="tournament-winner-info">
											<span class="winner-rank" v-if="item.digit">{{ item.digit }}</span>
											<span class="winner-school" v-if="item.club">, {{ item.club }}</span>
										</div>
									</div>
									<div class="tournament-winner-bottom">
										<div class="tournament-winner-title-info">
											<div class="tournament-winner-title">{{ item.description }}</div>
											<div class="tournament-winner-date">{{ item.date | date('DD MMMM YYYY') }}</div>
										</div>
										<div class="tournament-winner-score">
											<div class="tournament-winner-score-title">Результат:</div>
											<div class="tournament-winner-score-progress">
												<div class="tournament-winner-score-text"><span>{{ item.result }}</span>/{{ item.result_max }}</div>
												<client-only>
													<svg-progress-bar :value="item.result" :options="progressOptions"></svg-progress-bar>
												</client-only>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</template>

<script>
	export default {
		name: "winners",
		computed: {
			winners () {
				return this.$store.state['winners'] || []
			},
		},
		data () {
			return {
				progressOptions: {
					radius: 32,
					circleWidth: 3,
					pathColors: ['#d5d5d5', '#fa965c']
				},
			}
		}
	}
</script>