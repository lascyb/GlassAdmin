                <el-form-item label="<%label%>"<%required%>>
                    <el-radio-group v-model="form.<%prop%>">
                        <el-radio v-for="(item, value) in <%mapKey%>" :key="value" :value="Number(value)">
                            {{ item.label }}
                        </el-radio>
                    </el-radio-group>
                </el-form-item>
