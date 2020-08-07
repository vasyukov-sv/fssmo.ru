<template>
	<div v-if="types.length > 0" class="top-rating-container">
		<div class="top-rating-header">
			<h2 class="white">Рейтинг стрелков</h2>
		</div>
		<div class="row">
			<div class="col-sm-12">
				<div class="rating-list rating-list-large">
					<div class="row">
						<div class="rating-list-col" v-for="(type, i) in types" :key="i">
							<nuxt-link :to="'/ratings/'+type['code']+'/'" class="rating-item">
								<div class="rating-title">{{ type['title'] }}</div>
								<div class="rating-members">{{ type['count'] }} {{ type['count'] | morph('стрелок', 'стрелка', 'стрелков') }}</div>
							</nuxt-link>
						</div>
						<div class="rating-list-col rating-btn-col">
							<nuxt-link to="/ratings/" class="btn btn-gold">Все рейтинги</nuxt-link>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
	export default {
		data () {
			return {}
		},
		computed: {
			types () {
				return this.$store.state['ratingsTypes'] || []
			},
		},
	}
</script>