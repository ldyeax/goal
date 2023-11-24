export default {
	setup(props) {
		this.goal = props.app.latestGoals[props.id];
	},
	template: `
		<template>
			<div class="cmp-goal">
				<h1>{{ goal.name }}</h1>
			</div>
		</template>`
};
