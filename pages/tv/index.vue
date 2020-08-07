<template>
	<table ref="container" class="nohover">
		<tbody>
			<tr>
				<td v-for="col in cols" :width="(100 / cols)+'%'" valign="top" class="noborder">
					<table class="nohover">
						<tbody>
							<tr v-for="(r, index) in result" v-if="(col < cols && index >= pages[col] && index < pages[col+1]) || (col === cols && index >= pages[col])" :class="['group'+r['group']]">
								<td>
									{{ r['group'] ? r['group'] : 'Все' }}
								</td>
								<td>
									{{ r['digit'] }}
								</td>
								<td style="text-align:left; display:block; text-overflow:ellipsis;">
									{{ r['name'] }}
								</td>
								<td v-for="i in series" :class="{red: r['stands'][i] === 25, blue: r['stands'][i] === 24, green: r['stands'][i] === 23}">
									{{ r['stands'][i] > 0 ? r['stands'][i] : '' }}
								</td>
								<td class="bold">{{ r['summ'] > 0 ? r['summ'] : '&nbsp;&nbsp;&nbsp;' }}</td>
								<td class="bold">
									{{ r['place'] > 0 ? r['place'] : '&nbsp;&nbsp;&nbsp;' }}
								</td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>
		</tbody>
	</table>
</template>

<script>
	import '~/assets/css/tv.css'
	import gql from 'graphql-tag';

	const competitionQuery = gql`query ($id: String!) {
		competition (id: $id) {
			id
			max_shooters
			stands
		}
	}`;

	const resultListQuery = gql`query ($id: String!) {
		results: competitionResults (competition: $id) {
			place
			group
			number
			name
			summ
			country
			city
			club
			digit
			stands
		}
	}`;

	export default {
		layout: 'tv',
		async asyncData (context)
		{
			if (typeof context.query.id === 'undefined')
				return context.error('not found');

			let competitionRequest = context.app.$apollo.query({
				query: competitionQuery,
				variables: {
					id: context.query.id,
				},
			})

			let resultListRequest = context.app.$apollo.query({
				query: resultListQuery,
				variables: {
					id: context.query.id,
				},
			})

			let [competition, result] = await Promise.all([
				competitionRequest,
				resultListRequest,
			])

			return {
				competition: competition['data']['competition'],
				result: result['data']['results']
			}
		},
		computed: {
			series ()
			{
				let result = 0

				this.result.forEach((item) =>
				{
					if (Object.keys(item['stands']).length > result)
						result = Object.keys(item['stands']).length
				})

				return result
			},
			cols () {
				return parseInt(this.$route.query.cols || 2)
			},
			pages () {
				let p = Math.floor(this.result.length / this.cols)
				let result = {}

				let n = 0

				for (let i = 1; i <= this.cols; i++)
				{
					result[i] = n

					if (i < this.cols)
						n += p
				}

				return result
			}
		},
		mounted () {
			setInterval(() =>
			{
				this.$apollo.query({
					query: resultListQuery,
					variables: {
						id: this.$route.query.id,
					},
				})
				.then((data) => {
					this.result = data['data']['results']
					this.setFontSize()
				})
			}, 15000)

			window.onresize = () => {
				this.setFontSize()
			}

			this.setFontSize()
		},
		methods: {
			setFontSize () {
				this.$refs['container'].style.height = window.innerHeight+'px'
				document.querySelectorAll('body')[0].style.fontSize = (this.$route.query.font ? this.$route.query.font : Math.floor(this.result.length / this.cols) * window.innerHeight / (this.cols === 3 ? 1450 : 1850))+'px';
			}
		},
	}
</script>