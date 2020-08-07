<template>
	<div class="shedule-container snake">
		<div v-for="(gItems, index) in groups" class="shedule-container-col">
			<h4>{{ index }} группа</h4>
			<div class="shedule-container-table-wrap">
				<table>
					<thead>
						<tr>
							<th rowspan="2" class="time-col">№</th>
							<th rowspan="2" class="time-col">ФИО</th>
							<th :colspan="places.length">Площадки</th>
						</tr>
						<tr>
							<th v-for="place in places">
								{{ place }}
							</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="row in gItems">
							<td>{{ row['number'] }}</td>
							<td>{{ row['shooter'] }}</td>
							<td v-for="time in row['places']">
								{{ time | date('HH:mm') }}
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</template>

<script>
	export default {
		name: "sheduleSnake",
		props: {
			places: {
				type: Array,
				default: () => {
					return []
				}
			},
			items: {
				type: Array,
				default: () => {
					return []
				}
			},
		},
		computed: {
			groups ()
			{
				let result = {}

				this.items.forEach((shooter) =>
				{
					if (typeof result[shooter['group']] === 'undefined')
						result[shooter['group']] = []

					result[shooter['group']].push(shooter)
				})

				return result
			}
		},
	}
</script>