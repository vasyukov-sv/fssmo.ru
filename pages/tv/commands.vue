<template>
	<div class="camera_wrap">
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
								<template v-for="(command, i) in result">
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
										<td v-for="i in series" :class="{red: member['stands'][i] === 25, blue: member['stands'][i] === 24, green: member['stands'][i] === 23}">
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
				query: commandResultListQuery,
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

				this.result.forEach((command) =>
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
		mounted ()
		{
			setInterval(() =>
			{
				this.$apollo.query({
					query: commandResultListQuery,
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
				let max = this.result.reduce((sum, item) => {
				  return item['participants'].length + sum;
				}, 0);

				let fontSize = this.$route.query.font ? this.$route.query.font : (.7 * window.innerHeight / max * .6);

				document.querySelectorAll('body')[0].style.fontSize = fontSize + 'px';
			}
		}
	}
</script>