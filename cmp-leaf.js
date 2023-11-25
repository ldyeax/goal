import vGoal from "./cmp-goal.js";

export default {
	props: ["app", "leaf"],
	setup(props) {
		let app = props.app;
		let leaf = props.leaf;
		console.log(`rendering leaf ${JSON.stringify(leaf)}`);
		let id = leaf.id;
		return { id, app};
	},
	components: {
		"cmp-goal": vGoal
	},
	template: `
		<div class="cmp-leaf">
			<div class="cmp-leaf--root">
				<cmp-goal :app="app" :id="id"></cmp-goal>
			</div>
			<div class="cmp-leaf--children">
				<div v-for="child in leaf.children" style="border: 1px solid red; padding: 2px;">
					weight: {{child.weight}}
					<hr>
					<cmp-leaf :leaf="child" :app="app"></cmp-leaf>
				</div>
			</div>
		</div>`
};
