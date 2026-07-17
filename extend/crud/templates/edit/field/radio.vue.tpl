                <el-form-item label="<%label%>"<%required%>>
                    <el-radio-group v-model="form.<%prop%>">
                        <el-radio v-for="o in <%optKey%>" :key="o.value" :value="Number(o.value)">
                            {{ o.label }}
                        </el-radio>
                    </el-radio-group>
                </el-form-item>
