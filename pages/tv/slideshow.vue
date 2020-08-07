<template>
	<div class="swiper-container">
		<div ref="slider">
			<div class="swiper-wrapper">
				<div v-for="(items, group) in groups" class="swiper-slide">
					<table style="width:100%; background-color: #efefef;">
						<tbody>
						<tr>
							<td valign="top" align="center" class="noborder" width="25%"></td>
							<td valign="top" align="center" class="noborder" width="50%">
								<div id="groupInfo0" style=""><h2>Группа {{ group }}</h2>
									<table width="100%" id="container0">
										<tbody>
											<tr v-for="item in items" :class="['group'+group]">
												<td>{{ item['digit'] }}</td>
												<td>
													<div style="text-align:left; display:block; text-overflow:ellipsis;">{{ item['name'] }}</div>
												</td>
												<td v-for="i in seriesInd" :class="{red: item['stands'][i] === 25, blue: item['stands'][i] === 24, green: item['stands'][i] === 23}">
													{{ item['stands'][i] > 0 ? item['stands'][i] : '' }}
												</td>
												<td class="navy">{{ item['summ'] }}</td>
												<td class="bold">{{ item['place'] }}</td>
											</tr>
										</tbody>
									</table>
								</div>
							</td>
							<td valign="top" align="center" class="noborder" width="25%"></td>
						</tr>
						</tbody>
					</table>
				</div>
				<div class="swiper-slide camera_wrap">
					<table style="width:100%; background-color: #efefef;">
						<tbody>
						<tr>
							<td valign="top" align="center" class="noborder" width="25%"></td>
							<td valign="top" align="center" class="noborder" width="50%">
								<div id="groupInfo0" style="">
									<h2>Командный зачет</h2>
									<table width="100%" id="container0">
										<tbody>
											<tr>
												<td colspan="82" class="noborder">&nbsp;</td>
											</tr>
											<template v-for="(command, i) in resultCom">
												<tr class="cmd">
													<td colspan="8" class="bold">{{ command['command'] }}</td>
													<td class="bold" valign="middle" align="center" rowspan="4">{{ command['summ'] }}</td>
													<td class="bold" valign="middle" align="center" rowspan="4">{{ i + 1 }}</td>
												</tr>
												<tr v-for="member in command['participants']" class="cmd">
													<td>{{ member['digit'] }}</td>
													<td>
														<div style="text-align:left; display:block; text-overflow:ellipsis;">{{ member['name'] }}</div>
													</td>
													<td v-for="i in seriesCom" :class="{red: member['stands'][i] === 25, blue: member['stands'][i] === 24, green: member['stands'][i] === 23}">
														{{ member['stands'][i] > 0 ? member['stands'][i] : '' }}
													</td>
													<td class="navy">{{ member['summ'] }}</td>
													<td class="bold">{{ member['place'] }}</td>
												</tr>
												<tr><td colspan="82" class="noborder">&nbsp;</td></tr>
											</template>
										</tbody>
									</table>
								</div>
							</td>
							<td valign="top" align="center" class="noborder" width="25%"></td>
						</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
	import '~/assets/css/tv.css'
	import gql from 'graphql-tag';
	import { Swiper } from 'swiper/dist/js/swiper.esm'

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

	const commandResultListQuery = gql`query ($id: String!) {
		results: competitionCommandsResult (competition: $id) {
			command
			summ
			participants {
				number
				name
				summ
				club
				digit
				stands
				place
			}
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

			let resultListCommandRequest = context.app.$apollo.query({
				query: commandResultListQuery,
				variables: {
					id: context.query.id,
				},
			})

			let [competition, result, resultCom] = await Promise.all([
				competitionRequest,
				resultListRequest,
				resultListCommandRequest,
			])

			return {
				competition: competition['data']['competition'],
				result: result['data']['results'],
				resultCom: resultCom['data']['results'],
			}
		},
		computed: {
			groups ()
			{
				let groups = {};

				this.result.forEach((item) =>
				{
					if (item['group'] === null)
						item['group'] = '';

					if (groups[item['group']] === undefined)
						groups[item['group']] = [];

					groups[item['group']].push(item);
				});

				return groups;
			},
			seriesInd ()
			{
				let result = 0

				this.result.forEach((item) =>
				{
					if (Object.keys(item['stands']).length > result)
						result = Object.keys(item['stands']).length
				})

				return result
			},
			seriesCom ()
			{
				let result = 0

				this.resultCom.forEach((command) =>
				{
					command['participants'].forEach((item) =>
					{
						if (Object.keys(item['stands']).length > result)
							result = Object.keys(item['stands']).length
					})
				})

				return result
			},
		},
		mounted () {
			new Swiper(
				this.$refs['slider'],
				{
					loop: true,
					speed: 1500,
					autoplay: {
						delay: 10000,
					},
				}
			)

			setInterval(() =>
			{
				Promise.all([
					this.$apollo.query({
						query: resultListQuery,
						variables: {
							id: this.$route.query.id,
						},
					}),
					this.$apollo.query({
						query: commandResultListQuery,
						variables: {
							id: this.$route.query.id,
						},
					})
				])
				.then(([data, data2]) => {
					this.result = data['data']['results']
					this.resultCom = data2['data']['results']
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

				let max = 0;

				for (let i in this.groups)
				{
					if (max < this.groups[i].length)
						max = this.groups[i].length
				}

				let fontSize = this.$route.query.font ? this.$route.query.font : (.7 * window.innerHeight / max);

				document.querySelectorAll('body')[0].style.fontSize = fontSize + 'px';
			}
		}
	}
</script>