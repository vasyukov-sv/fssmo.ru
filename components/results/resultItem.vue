<template>
	<a :href="item.url" @click.prevent="click" class="result-item" :title="item.title">
		<div class="result-item-top">
			<div class="result-top-info">
				<div class="result-date">
					{{ item['date'] | date('DD MMMM YYYY') }}
					<div v-if="item['location']" class="result-location">{{ item['location'] }}</div>
				</div>
				<div class="result-competition-type" v-if="item['discipline']">{{ item['discipline'] }}</div>
			</div>
			<div class="result-competition-title">{{ item['title'] }}</div>
			<div class="result-competition-info">
				<div class="competition-members" v-if="item['members']">{{ item['members'] }} {{ item['members'] | morph('участник', 'участника', 'участников') }}</div>
				<div class="competition-groups" v-if="item['groups']">{{ item['groups'] }} {{ item['groups'] | morph('группа', 'группы', 'групп') }}</div>
			</div>
		</div>
		<div v-if="item['winner']" class="result-item-winner">
			<div class="winner-img" :style="{ 'background-image': item['winner']['photo'] ? 'url(' + item['winner']['photo'] + ')' : false }">
				<div class="result-winner-initials" v-if="!item['winner']['photo']">{{ item['winner']['name'][0] }}{{ item['winner']['last_name'][0] }}</div>
				<span class="winner-place">1</span>
			</div>
			<div class="winner-caption">
				<div class="winner-name">{{ item['winner']['name'] }} {{ item['winner']['last_name'] }}</div>
				<div class="winner-info">
					<div class="winner-score" v-if="item['winner']['summ']">{{ item['winner']['summ'] }}/{{ item['targets'] }}</div>
					<div class="winner-school" v-if="item['winner']['club'].length">{{ item['winner']['club'] }}</div>
				</div>
			</div>
		</div>
	</a>
</template>

<script>
	export default {
		name: "resultItem",
		props: {
			item: {
				type: Object
			}
		},
		computed: {
			isExternal () {
				return this.item['url'].indexOf('http') >= 0;
			}
		},
		data () {
			return {}
		},
		methods: {
			click ()
			{
				if (!this.isExternal)
				{
					if (!this.item['winner'])
						this.$router.push(this.item.url);
					else
						this.$router.push(this.item.url+'results/');
				}
				else
					window.open(this.item.url);
			}
		}
	}
</script>