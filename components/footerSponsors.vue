<template>
	<div class="container">
		<div class="row">
			<div class="col-sm-5 col-md-6">
				<h3 class="center">Генеральный спонсор</h3>
				<div v-for="item in items" v-if="item['type'] === 'general'" class="bottom-sponsor">
					<a :href="item['url']" target="_blank" rel="noopener">
						<img :src="item['image']" :alt="item['title']" :title="item['title']"/>
					</a>
				</div>
			</div>
			<div class="col-sm-7 col-md-6">
				<h3 class="center">Спонсоры и партнеры</h3>
				<div class="bottom-partners">
					<div v-for="item in items" v-if="item['type'] !== 'general'" class="partner-item">
						<a :href="item['url']" target="_blank" rel="noopener">
							<img :src="item['image']" :alt="item['title']" :title="item['title']"/>
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
	export default {
		name: "footerSponsors",
		computed: {
			items () {
				return this.$store.state['sponsors'] || []
			}
		}
	}
</script>