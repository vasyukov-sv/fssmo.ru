<template>
	<div class="top-rating-container">
		<div class="top-rating-header">
			<div class="row">
				<div class="col-lg-8 col-lg-push-4">
					<h2 class="white">Рейтинг стрелков</h2>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-8 col-lg-push-4">
				<div v-if="types.length > 0" class="rating-list">
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
			<div class="col-lg-4 col-lg-pull-8">
				<div class="best-result-search">
					<div class="best-result-search-bck"></div>
					<div class="best-result-search-content">
						<h3 class="center white">Лучший результат</h3>
						<div class="best-result-search-field">
							<input type="text" v-model="name" placeholder="Фамилия стрелка" @keydown.enter="search">
							<button type="button" :disabled="name.length < 3" class="btn btn-gold btn-small" @click.prevent="search">Искать</button>
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
			return {
				name: ''
			}
		},
		computed: {
			types () {
				return this.$store.state['ratingsTypes'] || []
			},
		},
		methods: {
			search ()
			{
				if (this.name.length < 3)
					return;

				this.$router.push({
					path: '/best-results/', query: {
						name: this.name
					}
				});
			}
		}
	}
</script>