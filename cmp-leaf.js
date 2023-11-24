import vGoal from "./cmp-goal.js";

export default {
	setup(props) {
		this.goal = props.app.latestGoals[props.id];
	},
	components: {
		"cmp-goal": vGoal
	},
	template: `
		<template>
			<div class="cmp-leaf--root">
				<cmp-goal></cmp-goal>
			</div>
			<div class="cmp-leaf--children">
				<cmp-leaf v-for="child in goal.children" :app="app" :id="child"></cmp-leaf>
			</div>
		</template>`
};
